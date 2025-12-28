<?php 
    // Get current page file name
    $currentPage = basename($_SERVER['PHP_SELF']); 
?>

<aside id="sidebar" class="fixed top-16 left-0 h-full bg-slate-800 text-white sidebar-transition z-20 w-64">
    <nav class="p-4">
        <ul class="space-y-2">

            <li>
                <a href="index.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'index.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-home mr-3"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>

            <li>
                <a href="clients.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'clients.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-users mr-3"></i>
                    <span class="sidebar-text">Clients</span>
                </a>
            </li>
            <li>
                <a href="generate-leads.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'generate-leads.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-circle-plus mr-3"></i>
                    <span class="sidebar-text">Traveller</span>
                </a>
            </li>
            <li>
                <a href="generate-leads.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'generate-leads.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-circle-plus mr-3"></i>
                    <span class="sidebar-text">Vendor</span>
                </a>
            </li>
            <li>
                <a href="generate-leads.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'generate-leads.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-circle-plus mr-3"></i>
                    <span class="sidebar-text">Generate Lead</span>
                </a>
            </li>

            <li>
                <a href="completed-work-entry.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'completed-work-entry.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-tasks mr-3"></i>
                    <span class="sidebar-text">Completed Work Entry</span>
                </a>
            </li>

            <!-- <li>
                <a href="completed-task-entry.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'completed-task-entry.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-tasks mr-3"></i>
                    <span class="sidebar-text">Completed Task Entry</span>
                </a>
            </li> -->

            <li>
                <a href="traveler-profile.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'traveler-profile.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-users mr-3"></i>
                    <span class="sidebar-text">Traveller's Profile</span>
                </a>
            </li>

            <li>
                <a href="analytics.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'analytics.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-chart-bar mr-3"></i>
                    <span class="sidebar-text">Analytics</span>
                </a>
            </li>

            <li>
                <a href="settings.php"
                   class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'settings.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-cog mr-3"></i>
                    <span class="sidebar-text">Settings</span>
                </a>
            </li>

        </ul>

        <div class="mt-8 pt-6 border-t border-slate-700 user-info">
            <div class="flex items-center p-3">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">JD</div>
                <div class="ml-3">
                    <p class="text-white font-medium">Tarekul Islam</p>
                    <p class="text-gray-400 text-sm">Managing Director</p>
                </div>
            </div>
        </div>

    </nav>
</aside>
