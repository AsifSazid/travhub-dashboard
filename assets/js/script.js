// UI Interactions JavaScript - Handles all UI interactions only
const UIInteractions = (function () {
    // Private variables
    let sidebarCollapsed = false;
    let notificationOpen = false;
    let userMenuOpen = false;
    let mobileMenuOpen = false;
    let mobileSearchOpen = false;
    let activeModal = null;
    
    // Accordion state
    let accordions = {
        'working-area': true,  // Default open
        'finance': true       // Default open
    };

    // DOM Elements
    const mainContent = document.getElementById('mainContent');
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationPanel = document.getElementById('notificationPanel');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileSearchBtn = document.getElementById('mobileSearchBtn');
    const mobileSearch = document.getElementById('mobileSearch');
    const modalOverlay = document.getElementById('modalOverlay');
    const modalClose = document.getElementById('modalClose');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');

    // Modal templates for UI modals only
    const modalTemplates = {
        workModal: {
            title: 'Add New Work',
            content: `
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Work Title</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option>Select a client</option>
                            <option>Sarah Johnson</option>
                            <option>Michael Chen</option>
                            <option>Emma Wilson</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="priority" class="text-blue-500" checked>
                                <span class="ml-2">Normal</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="priority" class="text-blue-500">
                                <span class="ml-2">High</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="priority" class="text-blue-500">
                                <span class="ml-2">Urgent</span>
                            </label>
                        </div>
                    </div>
                </div>
            `
        },
        decidedModal: {
            title: 'Add Decided Item',
            content: `<p>Decided modal content</p>`
        },
        infoModal: {
            title: 'Add Information to Provide',
            content: `<p>Information modal content</p>`
        },
        collectionModal: {
            title: 'Add Document Collection',
            content: `<p>Collection modal content</p>`
        },
        deliveryModal: {
            title: 'Add Document Delivery',
            content: `<p>Delivery modal content</p>`
        },
        editProgressModal: {
            title: 'Edit Progress',
            content: `<p>Edit progress modal content</p>`
        }
    };

    // Private methods
    function toggleSidebar() {
        sidebarCollapsed = !sidebarCollapsed;

        if (sidebarCollapsed) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-16', 'sidebar-collapsed');
            mainContent.classList.remove('pl-64');
            mainContent.classList.add('pl-16');

            document.querySelectorAll('#sidebar nav ul li a').forEach(link => {
                link.classList.add('justify-center');
                link.classList.remove('justify-start');
                const icon = link.querySelector('i');
                if (icon) {
                    icon.classList.remove('mr-3');
                }
            });

            // Hide sidebar text when collapsed
            document.querySelectorAll('.sidebar-text').forEach(text => {
                text.classList.add('hidden');
            });

            // Hide accordion content when sidebar collapsed
            document.querySelectorAll('.accordion-content').forEach(content => {
                content.classList.add('hidden');
            });
        } else {
            sidebar.classList.remove('w-16', 'sidebar-collapsed');
            sidebar.classList.add('w-64');
            mainContent.classList.remove('pl-16');
            mainContent.classList.add('pl-64');

            document.querySelectorAll('#sidebar nav ul li a').forEach(link => {
                link.classList.remove('justify-center');
                link.classList.add('justify-start');
                const icon = link.querySelector('i');
                if (icon) {
                    icon.classList.add('mr-3');
                }
            });

            // Show sidebar text when expanded
            document.querySelectorAll('.sidebar-text').forEach(text => {
                text.classList.remove('hidden');
            });

            // Show accordion content when sidebar expanded (if open)
            document.querySelectorAll('.accordion-item').forEach(item => {
                const accordionId = item.getAttribute('data-accordion');
                const content = item.querySelector('.accordion-content');
                if (accordions[accordionId] && content) {
                    content.classList.remove('hidden');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        }
    }

    function toggleNotificationPanel() {
        notificationOpen = !notificationOpen;
        if (notificationOpen) {
            notificationPanel.classList.remove('hidden');
            notificationPanel.classList.add('show');
            userMenu.classList.add('hidden');
            userMenu.classList.remove('show');
            userMenuOpen = false;
        } else {
            notificationPanel.classList.add('hidden');
            notificationPanel.classList.remove('show');
        }
    }

    function toggleUserMenu() {
        userMenuOpen = !userMenuOpen;
        if (userMenuOpen) {
            userMenu.classList.remove('hidden');
            userMenu.classList.add('show');
            notificationPanel.classList.add('hidden');
            notificationPanel.classList.remove('show');
            notificationOpen = false;
        } else {
            userMenu.classList.add('hidden');
            userMenu.classList.remove('show');
        }
    }

    function toggleMobileMenu() {
        mobileMenuOpen = !mobileMenuOpen;
        if (mobileMenuOpen) {
            mobileMenu.classList.remove('hidden');
        } else {
            mobileMenu.classList.add('hidden');
        }
    }

    function toggleMobileSearch() {
        mobileSearchOpen = !mobileSearchOpen;
        if (mobileSearchOpen) {
            mobileSearch.classList.remove('hidden');
        } else {
            mobileSearch.classList.add('hidden');
        }
    }

    // Accordion functions
    function toggleAccordion(accordionId) {
        const item = document.querySelector(`[data-accordion="${accordionId}"]`);
        if (!item) return;
        
        const toggleBtn = item.querySelector('.accordion-toggle');
        const content = item.querySelector('.accordion-content');
        const arrow = item.querySelector('.accordion-arrow');
        
        // Toggle state
        accordions[accordionId] = !accordions[accordionId];
        
        if (accordions[accordionId]) {
            // Open accordion
            toggleBtn.classList.add('active');
            content.classList.add('open');
            content.classList.remove('hidden');
            if (arrow) {
                arrow.style.transform = 'rotate(180deg)';
            }
            
            // Calculate height for smooth animation
            const scrollHeight = content.scrollHeight;
            content.style.maxHeight = scrollHeight + 'px';
            
            // After transition, set auto height
            setTimeout(() => {
                if (accordions[accordionId]) {
                    content.style.maxHeight = 'none';
                }
            }, 300);
        } else {
            // Close accordion
            toggleBtn.classList.remove('active');
            content.classList.remove('open');
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
            }
            
            // Set height before closing for smooth animation
            const scrollHeight = content.scrollHeight;
            content.style.maxHeight = scrollHeight + 'px';
            
            // Force reflow
            content.offsetHeight;
            
            // Start closing animation
            content.style.maxHeight = '0';
            
            // Hide after animation
            setTimeout(() => {
                if (!accordions[accordionId] && sidebarCollapsed) {
                    content.classList.add('hidden');
                }
            }, 300);
        }
    }

    function initAccordions() {
        // Add event listeners to all accordion toggle buttons
        document.querySelectorAll('.accordion-toggle').forEach(toggleBtn => {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const accordionItem = this.closest('.accordion-item');
                const accordionId = accordionItem.getAttribute('data-accordion');
                
                if (accordionId) {
                    toggleAccordion(accordionId);
                }
            });
        });

        // Set initial state based on accordions object
        Object.keys(accordions).forEach(id => {
            const item = document.querySelector(`[data-accordion="${id}"]`);
            if (item) {
                const toggleBtn = item.querySelector('.accordion-toggle');
                const content = item.querySelector('.accordion-content');
                const arrow = item.querySelector('.accordion-arrow');
                
                if (accordions[id]) {
                    // Open accordion
                    toggleBtn.classList.add('active');
                    content.classList.add('open');
                    content.classList.remove('hidden');
                    if (arrow) {
                        arrow.style.transform = 'rotate(180deg)';
                    }
                    
                    // Set height
                    const scrollHeight = content.scrollHeight;
                    content.style.maxHeight = scrollHeight + 'px';
                    
                    // After a moment, set to auto for dynamic content
                    setTimeout(() => {
                        if (accordions[id]) {
                            content.style.maxHeight = 'none';
                        }
                    }, 100);
                } else {
                    // Close accordion
                    toggleBtn.classList.remove('active');
                    content.classList.remove('open');
                    content.style.maxHeight = '0';
                    if (arrow) {
                        arrow.style.transform = 'rotate(0deg)';
                    }
                }
            }
        });

        // Highlight active page in accordion and open parent accordion
        const currentPath = window.location.pathname.split('/').pop() || 'index.php';
        const currentPage = '<?= isset($currentPage) ? $currentPage : "" ?>' || currentPath;
        
        // First check all sidebar links
        document.querySelectorAll('#sidebar a').forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || href === currentPath) {
                link.classList.add('active');
                
                // Ensure parent accordion is open
                const accordionItem = link.closest('.accordion-item');
                if (accordionItem) {
                    const accordionId = accordionItem.getAttribute('data-accordion');
                    if (accordionId && !accordions[accordionId]) {
                        toggleAccordion(accordionId);
                    }
                }
            }
        });
    }

    function openModal(modalId) {
        const template = modalTemplates[modalId];
        if (template) {
            modalTitle.textContent = template.title;
            modalContent.innerHTML = template.content;
            modalOverlay.classList.remove('hidden');
            activeModal = modalId;
        }
    }

    function closeModal() {
        modalOverlay.classList.add('hidden');
        activeModal = null;
    }

    function checkScreenSize() {
        const width = window.innerWidth;

        if (width < 768) {
            document.querySelectorAll('.kanban-column').forEach(col => {
                col.style.minHeight = '400px';
                col.style.maxHeight = '500px';
            });
            document.body.classList.add('mobile-view');
            document.body.classList.remove('desktop-view');
            
            // Auto close sidebar on mobile if open
            if (!sidebarCollapsed && sidebarToggle) {
                toggleSidebar();
            }
        } else if (width < 1024) {
            document.querySelectorAll('.kanban-column').forEach(col => {
                col.style.minHeight = '600px';
            });
            document.body.classList.add('tablet-view');
            document.body.classList.remove('mobile-view');
        } else {
            document.querySelectorAll('.kanban-column').forEach(col => {
                col.style.minHeight = 'calc(100vh - 8rem)';
            });
            document.body.classList.add('desktop-view');
            document.body.classList.remove('tablet-view');
        }
    }

    function initEventListeners() {
        // Initialize accordions
        initAccordions();

        // Sidebar toggle
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        // Notification panel
        if (notificationBtn && notificationPanel) {
            notificationBtn.addEventListener('click', toggleNotificationPanel);
        }

        // User menu
        if (userMenuBtn && userMenu) {
            userMenuBtn.addEventListener('click', toggleUserMenu);
        }

        // Mobile menu
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        }

        // Mobile search
        if (mobileSearchBtn && mobileSearch) {
            mobileSearchBtn.addEventListener('click', toggleMobileSearch);
        }

        // Modal close
        if (modalClose) {
            modalClose.addEventListener('click', closeModal);
        }

        if (modalOverlay) {
            modalOverlay.addEventListener('click', function (e) {
                if (e.target === modalOverlay) {
                    closeModal();
                }
            });
        }

        // Add buttons (for UI modals only)
        document.querySelectorAll('.kanban-btn-add').forEach(button => {
            button.addEventListener('click', function () {
                const modalId = this.getAttribute('data-modal');
                if (modalId) {
                    openModal(modalId);
                }
            });
        });

        // Edit buttons
        document.addEventListener('click', function (e) {
            const editBtn = e.target.closest('.kanban-btn-edit');
            if (editBtn) {
                openModal('editProgressModal');
            }
        });

        // Quick access buttons
        document.querySelectorAll('.quick-access-btn').forEach(button => {
            button.addEventListener('click', function () {
                console.log('Quick access button clicked');
            });
        });

        // Close menus when clicking outside
        document.addEventListener('click', function (e) {
            if (notificationBtn && !notificationBtn.contains(e.target) &&
                notificationPanel && !notificationPanel.contains(e.target)) {
                notificationPanel.classList.add('hidden');
                notificationPanel.classList.remove('show');
                notificationOpen = false;
            }

            if (userMenuBtn && !userMenuBtn.contains(e.target) &&
                userMenu && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
                userMenu.classList.remove('show');
                userMenuOpen = false;
            }

            if (mobileMenuBtn && !mobileMenuBtn.contains(e.target) &&
                mobileMenu && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.add('hidden');
                mobileMenuOpen = false;
            }

            if (mobileSearchBtn && !mobileSearchBtn.contains(e.target) &&
                mobileSearch && !mobileSearch.contains(e.target)) {
                mobileSearch.classList.add('hidden');
                mobileSearchOpen = false;
            }
        });

        // Window resize
        window.addEventListener('resize', checkScreenSize);
        
        // Close accordions when sidebar is collapsed (optional)
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                setTimeout(() => {
                    if (sidebarCollapsed) {
                        // When sidebar is collapsed, ensure accordion content is hidden
                        document.querySelectorAll('.accordion-content').forEach(content => {
                            content.classList.add('hidden');
                        });
                    }
                }, 300);
            });
        }
    }

    function init() {
        checkScreenSize();
        initEventListeners();

        // Touch device detection
        if ('ontouchstart' in window || navigator.maxTouchPoints) {
            document.body.classList.add('touch-device');

            document.querySelectorAll('.column-scroll').forEach(el => {
                el.style.webkitOverflowScrolling = 'touch';
            });
        }
        
        // Initialize active page highlighting
        highlightActivePage();
    }
    
    function highlightActivePage() {
        const currentPath = window.location.pathname.split('/').pop() || 'index.php';
        const currentPage = '<?= isset($currentPage) ? $currentPage : "" ?>' || currentPath;
        
        // Remove all active classes first
        document.querySelectorAll('#sidebar a').forEach(link => {
            link.classList.remove('bg-slate-700', 'text-white');
            link.classList.add('text-gray-300');
        });
        
        // Add active class to current page
        document.querySelectorAll('#sidebar a').forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || href === currentPath) {
                link.classList.remove('text-gray-300');
                link.classList.add('bg-slate-700', 'text-white');
            }
        });
    }

    // Public API
    return {
        init: init,
        openModal: openModal,
        closeModal: closeModal,
        toggleSidebar: toggleSidebar,
        toggleNotificationPanel: toggleNotificationPanel,
        toggleUserMenu: toggleUserMenu,
        toggleAccordion: toggleAccordion,
        highlightActivePage: highlightActivePage
    };
})();

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    UIInteractions.init();
});