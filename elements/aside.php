<?php
// Get current page file name
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside id="sidebar" class="fixed top-16 left-0 h-[calc(100vh-4rem)] bg-slate-800 text-white z-20 w-64 flex flex-col">
    <nav class="flex-1 overflow-y-auto p-4 ">
        <ul class="space-y-2">

            <li>
                <a href="index.php"
                    class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'index.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-home mr-3"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>

            <!-- Stake Holder Accordion -->
            <div class="accordion-item mt-4" data-accordion="stake-holder">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-briefcase mr-3"></i>
                        <span class="font-medium">Stake Holders</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 overflow-hidden max-h-0 transition-all duration-300">
                    <a href="index-clients.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-clients.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="sidebar-text">Clients</span>
                    </a>
                    <a href="index-travelers.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-travelers.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="sidebar-text">Traveller's Profile</span>
                    </a>
                    <a href="index-vendors.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-vendors.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="sidebar-text">Vendors</span>
                    </a>
                </div>
            </div>
            
            <!-- Working Area Accordion -->
            <div class="accordion-item mt-4" data-accordion="works-area">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-briefcase mr-3"></i>
                        <span class="font-medium">Working Area</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 overflow-hidden max-h-0 transition-all duration-300">
                    <a href="generate-leads.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'generate-leads.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-circle-plus mr-3"></i>
                        <span class="sidebar-text">Generate Lead</span>
                    </a>
                    <a href="completed-work-entry.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'completed-work-entry.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-tasks mr-3"></i>
                        <span class="sidebar-text">Completed Work Entry</span>
                    </a>
                </div>
            </div>
            
            <!-- Finance Accordion -->
            <div class="accordion-item mt-4" data-accordion="fin">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-coins mr-3"></i>
                        <span class="font-medium">Finance</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 overflow-hidden max-h-0 transition-all duration-300">
                    <a href="accounts.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'accounts.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-bangladeshi-taka-sign mr-3"></i>
                        <span class="sidebar-text">Accounting</span>
                    </a>
                    <a href="create-invoice.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'create-invoice.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-receipt mr-3"></i>
                        <span class="sidebar-text">Create Invoice</span>
                    </a>
                    <a href="analytics.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'analytics.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-chart-bar mr-3"></i>
                        <span class="sidebar-text">Analytics</span>
                    </a>
                </div>
            </div>
            
            <!-- HR Accordion -->
            <div class="accordion-item mt-4" data-accordion="hr">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-user-tie mr-3"></i>
                        <span class="font-medium">Human Resource (HR)</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 overflow-hidden max-h-0 transition-all duration-300">
                    <a href="index-employees.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-employees.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-user-tie mr-3"></i>
                        <span class="sidebar-text">Employees</span>
                    </a>
                    <!--<a href="create-invoice.php"-->
                    <!--    class="flex items-center p-3 rounded-lg -->
                    <!--   <?= $currentPage == 'create-invoice.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">-->
                    <!--    <i class="fa-solid fa-bangladeshi-taka-sign mr-3"></i>-->
                    <!--    <span class="sidebar-text">Create Invoice</span>-->
                    <!--</a>-->
                    <!--<a href="analytics.php"-->
                    <!--    class="flex items-center p-3 rounded-lg -->
                    <!--   <?= $currentPage == 'analytics.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">-->
                    <!--    <i class="fas fa-chart-bar mr-3"></i>-->
                    <!--    <span class="sidebar-text">Analytics</span>-->
                    </a>
                </div>
            </div>
            
            <!-- Tools Accordion -->
            <div class="accordion-item mt-4" data-accordion="tools">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-gears mr-3"></i>
                        <span class="font-medium">Tools</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 overflow-hidden max-h-0 transition-all duration-300">
                    <a href="passport-info-extraction.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'passport-info-extraction.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-passport mr-3"></i>
                        <span class="sidebar-text">Passport Info Extraction</span>
                    </a>
                </div>
            </div>
            
            <li>
                <a href="settings.php"
                    class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'settings.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-cog mr-3"></i>
                    <span class="sidebar-text">Settings</span>
                </a>
            </li>

        </ul>
    </nav>
    
    <div class="border-t flex items-center border-slate-700 p-4 h-28">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                TI
            </div>
            <div class="ml-3">
                <p class="text-white font-medium">Tarekul Islam</p>
                <p class="text-gray-400 text-sm">Managing Director</p>
            </div>
            <a href="#"
                class="flex items-center p-3 rounded-lg" title="logout">
                <i class="fa-solid fa-arrow-right-from-bracket text-xl ml-2"></i>
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
    }

    .accordion-arrow {
        transition: transform 0.3s ease;
    }

    .accordion-toggle.active .accordion-arrow {
        transform: rotate(180deg);
    }

    .accordion-content {
        max-height: 0;
        opacity: 0;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .accordion-content.open {
        max-height: 500px;
        opacity: 1;
    }

    /* Active page styles for accordion items */
    .accordion-content a.active {
        background-color: rgb(51 65 85);
        color: white;
    }

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
    .sidebar-collapsed .accordion-toggle span,
    .sidebar-collapsed .accordion-toggle i:first-child {
        display: none;
    }

    .sidebar-collapsed .accordion-toggle {
        justify-content: center;
        padding: 0.75rem;
    }

    .sidebar-collapsed .accordion-arrow {
        display: none;
    }

    .sidebar-collapsed .accordion-content {
        display: none !important;
    }
</style>