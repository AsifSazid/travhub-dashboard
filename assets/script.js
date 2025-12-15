const sidebarToggler = (function () {
    // Private variables
    let sidebarCollapsed = false;
    let notificationOpen = false;
    let userMenuOpen = false;

    // DOM Element
    const mainContent = document.getElementById('mainContent');
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationPanel = document.getElementById('notificationPanel');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');

    // Update the toggleSidebar function in your JavaScript
    function toggleSidebar() {
        sidebarCollapsed = !sidebarCollapsed;

        if (sidebarCollapsed) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-16', 'sidebar-collapsed');
            mainContent.classList.remove('pl-64');
            mainContent.classList.add('pl-16');

            // Center align all icons when collapsed
            document.querySelectorAll('#sidebar nav ul li a').forEach(link => {
                link.classList.add('justify-center');
                link.classList.remove('justify-start');
            });
        } else {
            sidebar.classList.remove('w-16', 'sidebar-collapsed');
            sidebar.classList.add('w-64');
            mainContent.classList.remove('pl-16');
            mainContent.classList.add('pl-64');

            // Reset alignment when expanded
            document.querySelectorAll('#sidebar nav ul li a').forEach(link => {
                link.classList.remove('justify-center');
                link.classList.add('justify-start');
            });
        }
    }

    function toggleNotificationPanel() {
        notificationOpen = !notificationOpen;
        if (notificationOpen) {
            notificationPanel.classList.add('show');
            userMenu.classList.remove('show');
            userMenuOpen = false;
        } else {
            notificationPanel.classList.remove('show');
        }
    }

    function toggleUserMenu() {
        userMenuOpen = !userMenuOpen;
        if (userMenuOpen) {
            userMenu.classList.add('show');
            notificationPanel.classList.remove('show');
            notificationOpen = false;
        } else {
            userMenu.classList.remove('show');
        }
    }

    function initEventListeners() {
        // Sidebar toggle
        sidebarToggle.addEventListener('click', toggleSidebar);

        // Notification panel
        notificationBtn.addEventListener('click', toggleNotificationPanel);

        // User menu
        userMenuBtn.addEventListener('click', toggleUserMenu);

        // Close menus when clicking outside
        document.addEventListener('click', function (e) {
            if (!notificationBtn.contains(e.target) && !notificationPanel.contains(e.target)) {
                notificationPanel.classList.remove('show');
                notificationOpen = false;
            }

            if (!userMenuBtn.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('show');
                userMenuOpen = false;
            }
        });

    }

    // Public API
    return {
        init: function () {
            initEventListeners();
        },

        // Public methods that can be called from outside
        toggleSidebar: toggleSidebar,
    };

})();

// Initialize the dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    sidebarToggler.init();
});