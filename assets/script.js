// Main Dashboard JavaScript - Combined Version
const DashboardApp = (function () {
    // Private variables
    let sidebarCollapsed = false;
    let notificationOpen = false;
    let userMenuOpen = false;
    let mobileMenuOpen = false;
    let mobileSearchOpen = false;
    let activeModal = null;
    let currentLeadId = null;

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

    // Column references
    const columns = {
        leads: document.getElementById('leadsColumn'),
        newWork: document.getElementById('newWorkColumn'),
        inProgress: document.getElementById('inProgressColumn'),
        submittedWork: document.getElementById('submittedWorkColumn'),
        decidedToday: document.getElementById('decidedTodayColumn'),
        infoToProvide: document.getElementById('infoToProvideColumn'),
        docCollection: document.getElementById('docCollectionColumn'),
        docDelivery: document.getElementById('docDeliveryColumn')
    };

    // Modal templates
    const modalTemplates = {
        leadModal: {
            title: 'Add New Lead',
            content: `
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client Name</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option>Business Visa</option>
                            <option>Work Permit</option>
                            <option>Permanent Residency</option>
                            <option>Citizenship</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                    </div>
                </div>
            `
        },
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
        leadDetailsModal: {
            title: 'Lead Details',
            content: document.getElementById('leadDetailsModalTemplate')?.innerHTML || ''
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

    function openModal(modalId, leadData = null) {
        if (modalId === 'leadDetailsModal' && leadData) {
            openLeadDetailsModal(leadData);
        } else {
            const template = modalTemplates[modalId];
            if (template) {
                modalTitle.textContent = template.title;
                modalContent.innerHTML = template.content;
                modalOverlay.classList.remove('hidden');
                activeModal = modalId;
            }
        }
    }

    function openLeadDetailsModal(leadData) {
        const template = modalTemplates.leadDetailsModal;
        if (!template) return;

        modalTitle.textContent = 'Lead Details';
        modalContent.innerHTML = template;

        setTimeout(() => {
            // Fill modal with lead data
            const clientInfo = JSON.parse(leadData.client_info || '{}');

            // Set basic information
            const nameElement = document.getElementById('leadModalName');
            const serviceElement = document.getElementById('leadModalService');
            const dateElement = document.getElementById('leadModalDate');

            if (nameElement) nameElement.textContent = clientInfo.name || 'N/A';

            let serviceText = 'N/A';
            if (leadData.service_type) {
                try {
                    const services = JSON.parse(leadData.service_type);
                    serviceText = Array.isArray(services) ?
                        services.map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(', ') :
                        services;
                } catch (e) {
                    serviceText = leadData.service_type;
                }
            }
            if (serviceElement) serviceElement.textContent = serviceText;

            if (dateElement) {
                dateElement.textContent = new Date(leadData.created_at).toLocaleDateString('en-US', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            }

            // Set client details
            const emailElement = document.getElementById('leadModalEmail');
            const phoneElement = document.getElementById('leadModalPhone');
            const countryElement = document.getElementById('leadModalCountry');
            const visaTypeElement = document.getElementById('leadModalVisaType');

            if (emailElement) emailElement.textContent = clientInfo.emails?.[0] || clientInfo.email || 'N/A';
            if (phoneElement) phoneElement.textContent = clientInfo.phones?.[0] || clientInfo.phone || 'N/A';

            let country = 'N/A';
            let visa_type = 'N/A';

            if (leadData.service_data) {
                try {
                    const serviceData = JSON.parse(leadData.service_data);
                    for (const key in serviceData) {
                        if (serviceData[key]?.country || serviceData[key]?.visaCategory) {
                            country = serviceData[key].country?.toUpperCase() || 'N/A';
                            visa_type = serviceData[key].visaCategory?.toUpperCase() || 'N/A';
                            break;
                        }
                    }
                } catch (e) {
                    console.error('Invalid service_data JSON', e);
                }
            }

            if (countryElement) countryElement.textContent = country;
            if (visaTypeElement) visaTypeElement.textContent = visa_type;

            // Set additional information
            const additionalInfoDiv = document.getElementById('leadModalAdditionalInfo');
            if (additionalInfoDiv) {
                if (leadData.additional_info && leadData.additional_info !== 'null') {
                    try {
                        const additionalInfo = JSON.parse(leadData.additional_info);
                        let html = '';
                        for (const [key, value] of Object.entries(additionalInfo)) {
                            if (value) {
                                html += `<div class="mb-2">
                                    <p class="text-xs text-gray-500">${key.replace('_', ' ').toUpperCase()}</p>
                                    <p class="text-sm">${value}</p>
                                </div>`;
                            }
                        }
                        if (html === '') {
                            html = '<p class="text-gray-500 text-sm">No additional information available.</p>';
                        }
                        additionalInfoDiv.innerHTML = html;
                    } catch (e) {
                        additionalInfoDiv.innerHTML = `<p class="text-sm">${leadData.additional_info}</p>`;
                    }
                } else {
                    additionalInfoDiv.innerHTML = '<p class="text-gray-500 text-sm">No additional information available.</p>';
                }
            }

            // Set notes
            const notesElement = document.getElementById('leadModalNotes');
            if (notesElement && leadData.notes) {
                notesElement.value = leadData.notes;
            }

            // Store lead ID
            modalContent.dataset.leadId = leadData.id;
            currentLeadId = leadData.id;

            // Show modal
            modalOverlay.classList.remove('hidden');
            activeModal = 'leadDetailsModal';
        }, 0);
    }

    function closeModal() {
        modalOverlay.classList.add('hidden');
        activeModal = null;
        currentLeadId = null;
    }

    function moveCard(card, fromColumn, toColumn) {
        card.style.transform = 'translateX(20px)';
        card.style.opacity = '0.7';

        setTimeout(() => {
            card.remove();
            const newCard = card.cloneNode(true);
            newCard.style.transform = '';
            newCard.style.opacity = '';

            const moveBtn = newCard.querySelector('.kanban-btn-move');
            if (moveBtn) {
                if (toColumn === 'newWork') {
                    moveBtn.setAttribute('data-from', 'newWork');
                    moveBtn.setAttribute('data-to', 'inProgress');
                } else if (toColumn === 'inProgress') {
                    moveBtn.innerHTML = '<i class="fas fa-edit"></i>';
                    moveBtn.className = 'kanban-btn-edit text-gray-400 hover:text-blue-500';
                    moveBtn.setAttribute('data-card-type', 'inProgress');
                }
            }

            const loadMoreBtn = columns[toColumn]?.querySelector('.kanban-btn-load-more');
            if (loadMoreBtn && columns[toColumn]) {
                columns[toColumn].insertBefore(newCard, loadMoreBtn);
            } else if (columns[toColumn]) {
                columns[toColumn].appendChild(newCard);
            }

            // Re-attach event listeners
            reattachCardEventListeners(newCard, toColumn);

            if (newCard.classList.contains('lead-card')) {
                newCard.addEventListener('click', function (e) {
                    if (!e.target.closest('.kanban-btn-move') && !e.target.closest('.kanban-btn-edit')) {
                        const leadData = JSON.parse(newCard.dataset.leadInfo);
                        openLeadDetailsModal(leadData);
                    }
                });
            }
        }, 300);
    }

    function reattachCardEventListeners(card, columnType) {
        if (columnType === 'inProgress') {
            const editBtn = card.querySelector('.kanban-btn-edit');
            if (editBtn) {
                editBtn.addEventListener('click', function () {
                    openModal('editProgressModal');
                });
            }
        } else {
            const moveBtn = card.querySelector('.kanban-btn-move');
            if (moveBtn) {
                moveBtn.addEventListener('click', function () {
                    const from = this.getAttribute('data-from');
                    const to = this.getAttribute('data-to');
                    moveCard(card, from, to);
                });
            }
        }

        if (card.classList.contains('lead-card')) {
            card.addEventListener('click', function (e) {
                if (!e.target.closest('.kanban-btn-move') && !e.target.closest('.kanban-btn-edit')) {
                    const leadData = JSON.parse(card.dataset.leadInfo);
                    openLeadDetailsModal(leadData);
                }
            });
        }
    }

    function loadMoreItems(columnId) {
        const sampleData = {
            leads: {
                title: 'David Miller',
                description: 'Student Visa Application',
                time: 'Just now',
                country: 'Australia'
            },
            newWork: {
                title: 'Jennifer Lopez',
                description: 'Tourist Visa Extension',
                time: '30 minutes ago'
            }
        };

        const data = sampleData[columnId];
        const column = columns[columnId];
        if (!column) return;

        const loadMoreBtn = column.querySelector('.kanban-btn-load-more');
        let newCard;

        switch (columnId) {
            case 'leads':
                newCard = document.createElement('div');
                newCard.className = 'kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 lead-card';
                newCard.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-medium text-gray-800">${data.title}</h3>
                            <p class="text-sm text-gray-600 mt-1">${data.description}</p>
                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                <span>${data.time}</span>
                            </div>
                            <div class="flex items-center mt-1 text-xs text-gray-500">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                <span>${data.country}</span>
                            </div>
                        </div>
                        <button class="kanban-btn-move text-gray-400 hover:text-blue-500" data-from="${columnId}" data-to="newWork">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                `;
                newCard.dataset.leadId = 'sample-' + Date.now();
                newCard.dataset.leadInfo = JSON.stringify({
                    id: newCard.dataset.leadId,
                    client_info: JSON.stringify({
                        name: data.title,
                        email: 'sample@example.com',
                        phone: '+1234567890',
                        country: data.country,
                        visa_type: 'Student'
                    }),
                    service_type: data.description,
                    created_at: new Date().toISOString(),
                    notes: ''
                });
                break;

            default:
                newCard = document.createElement('div');
                newCard.className = 'kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover';
                newCard.innerHTML = `
                    <h3 class="font-medium text-gray-800">New ${columnId} Item</h3>
                    <p class="text-sm text-gray-600 mt-1">Additional content loaded for ${columnId}</p>
                    <div class="flex items-center mt-2 text-xs text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        <span>Just now</span>
                    </div>
                `;
        }

        if (loadMoreBtn && column) {
            column.insertBefore(newCard, loadMoreBtn);
        } else if (column) {
            column.appendChild(newCard);
        }

        reattachCardEventListeners(newCard, columnId);
    }

    function saveLeadChanges() {
        const leadId = currentLeadId;
        const notes = document.getElementById('leadModalNotes')?.value || '';

        const saveBtn = modalContent.querySelector('.kanban-modal-btn-save');
        if (!saveBtn) return;

        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
        saveBtn.disabled = true;

        setTimeout(() => {
            console.log('Saving lead:', { leadId, notes });
            saveBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Saved!';

            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
                const leadCard = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
                if (leadCard) {
                    const leadData = JSON.parse(leadCard.dataset.leadInfo);
                    leadData.notes = notes;
                    leadCard.dataset.leadInfo = JSON.stringify(leadData);
                }
                closeModal();
            }, 1000);
        }, 1500);
    }

    function convertLeadToWork() {
        const leadId = currentLeadId;

        if (confirm('Convert this lead to a new work item?')) {
            const convertBtn = modalContent.querySelector('.kanban-modal-btn-convert');
            if (!convertBtn) return;

            const originalText = convertBtn.innerHTML;
            convertBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Converting...';
            convertBtn.disabled = true;

            setTimeout(() => {
                console.log('Converting lead to work:', { leadId });
                convertBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Converted!';

                const leadCard = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
                if (leadCard) {
                    moveCard(leadCard, 'leads', 'newWork');
                }

                setTimeout(() => {
                    convertBtn.innerHTML = originalText;
                    convertBtn.disabled = false;
                    closeModal();
                }, 1000);
            }, 1500);
        }
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

        // Add buttons
        document.querySelectorAll('.kanban-btn-add').forEach(button => {
            button.addEventListener('click', function () {
                const modalId = this.getAttribute('data-modal');
                if (modalId) {
                    openModal(modalId);
                }
            });
        });

        // Move buttons
        document.addEventListener('click', function (e) {
            const moveBtn = e.target.closest('.kanban-btn-move');
            if (moveBtn) {
                const card = moveBtn.closest('.kanban-card');
                const from = moveBtn.getAttribute('data-from');
                const to = moveBtn.getAttribute('data-to');
                if (card && from && to) {
                    moveCard(card, from, to);
                }
            }
        });

        // Edit buttons
        document.addEventListener('click', function (e) {
            const editBtn = e.target.closest('.kanban-btn-edit');
            if (editBtn) {
                openModal('editProgressModal');
            }
        });

        // Load more buttons
        document.addEventListener('click', function (e) {
            const loadMoreBtn = e.target.closest('.kanban-btn-load-more');
            if (loadMoreBtn) {
                const columnId = loadMoreBtn.getAttribute('data-column');
                loadMoreItems(columnId);
            }
        });

        // Lead card click events
        document.addEventListener('click', function (e) {
            const leadCard = e.target.closest('.kanban-card[data-lead-id]');
            if (leadCard && !e.target.closest('.kanban-btn-move') && !e.target.closest('.kanban-btn-edit')) {
                const leadData = JSON.parse(leadCard.dataset.leadInfo);
                openLeadDetailsModal(leadData);
            }
        });

        // Modal action buttons
        document.addEventListener('click', function (e) {
            const target = e.target;
            const btn = target.closest('button');

            if (!btn) return;

            if (btn.classList.contains('kanban-modal-btn-save')) {
                saveLeadChanges();
            }

            if (btn.classList.contains('kanban-modal-btn-convert')) {
                convertLeadToWork();
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
    }

    // Public API
    return {
        init: init,
        openModal: openModal,
        closeModal: closeModal,
        openLeadDetails: function (leadId) {
            const leadCard = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
            if (leadCard) {
                const leadData = JSON.parse(leadCard.dataset.leadInfo);
                openLeadDetailsModal(leadData);
            }
        },
        toggleSidebar: toggleSidebar
    };
})();

// Initialize the dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    DashboardApp.init();
});