// Dashboard Functionality JavaScript
const DashboardApp = (function () {
    // Private variables
    let currentLeadId = null;
    let leadsData = [];

    // DOM Elements
    const modalOverlay = document.getElementById('modalOverlay');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    const modalClose = document.getElementById('modalClose');

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

    // Private methods
    function loadLeadsFromAPI() {
        fetch(API_URL_FOR_ALL_LEADS)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.leads) {
                    leadsData = data.leads;
                    renderLeads();
                } else {
                    renderEmptyLeads();
                }
            })
            .catch(error => {
                console.error('Error fetching leads:', error);
                renderEmptyLeads();
            });
    }

    function renderLeads() {
        const leadsColumn = columns.leads;
        if (!leadsColumn) return;

        // Clear existing content except load more button
        const loadMoreBtn = leadsColumn.querySelector('.kanban-btn-load-more');
        leadsColumn.innerHTML = '';

        if (leadsData.length === 0) {
            renderEmptyLeads();
            if (loadMoreBtn) leadsColumn.appendChild(loadMoreBtn);
            return;
        }

        // Create lead cards
        leadsData.forEach(lead => {
            const leadCard = createLeadCard(lead);
            leadsColumn.appendChild(leadCard);
        });

        // Add load more button back
        if (loadMoreBtn) leadsColumn.appendChild(loadMoreBtn);
    }

    function createLeadCard(lead) {
        const clientInfo = lead.client_info ? JSON.parse(lead.client_info) : {};
        const serviceData = lead.service_data ? JSON.parse(lead.service_data) : {};

        const name = clientInfo.name || 'Unknown';
        const email = clientInfo.email || 'N/A';
        const phone = clientInfo.phone || 'N/A';

        // Service type display
        let displayService = 'N/A';
        if (lead.service_type) {
            try {
                const decodedType = JSON.parse(lead.service_type);
                if (Array.isArray(decodedType)) {
                    displayService = decodedType.map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(', ');
                } else {
                    displayService = lead.service_type;
                }
            } catch (e) {
                displayService = lead.service_type;
            }
        }

        // Country
        let country = 'N/A';
        if (Array.isArray(serviceData) && serviceData.length > 0) {
            const firstService = serviceData[0];
            country = firstService.country ? firstService.country.toUpperCase() : 'N/A';
        }

        // Date formatting
        const submittedAt = lead.created_at
            ? formatDate(lead.created_at)
            : 'Unknown';

        // Create card element
        const card = document.createElement('div');
        card.className = 'kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 lead-card';
        card.dataset.leadId = lead.id;
        card.dataset.leadInfo = JSON.stringify(lead);

        card.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-medium text-gray-800">${escapeHtml(name)}</h3>
                    <p class="text-sm text-gray-600 mt-1">${escapeHtml(displayService)}</p>
                    <div class="flex items-center mt-2 text-xs text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        <span>${submittedAt}</span>
                    </div>
                    <div class="flex items-center mt-1 text-xs text-gray-500">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <span>${escapeHtml(country)}</span>
                    </div>
                </div>
                <button class="kanban-btn-move text-gray-400 hover:text-blue-500"
                    data-from="leads"
                    data-to="newWork">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        `;

        // Add click event for lead details
        card.addEventListener('click', function (e) {
            if (!e.target.closest('.kanban-btn-move') && !e.target.closest('.kanban-btn-edit')) {
                openLeadDetailsModal(lead);
            }
        });

        return card;
    }

    function renderEmptyLeads() {
        const leadsColumn = columns.leads;
        if (!leadsColumn) return;

        const emptyMessage = document.createElement('p');
        emptyMessage.className = 'text-gray-500 text-sm text-center py-4';
        emptyMessage.textContent = 'No pending leads found.';

        leadsColumn.appendChild(emptyMessage);
    }

    function openLeadDetailsModal(leadData) {
        // Parse data first
        let clientInfo = {};
        try {
            if (leadData.client_info && typeof leadData.client_info === 'string') {
                clientInfo = JSON.parse(leadData.client_info);
            } else if (leadData.client_info) {
                clientInfo = leadData.client_info;
            }
        } catch (e) {
            console.error('Error parsing client_info:', e);
        }

        // Get template
        const template = document.getElementById('leadDetailsModalTemplate');
        if (!template) {
            console.error('Template not found');
            return;
        }

        // Clone template
        const templateContent = template.content.cloneNode(true);

        // Populate template
        // Name
        const nameElement = templateContent.getElementById('leadModalName');
        if (nameElement) nameElement.textContent = clientInfo.name || 'N/A';

        // Service type
        let serviceText = 'N/A';
        if (leadData.service_type) {
            try {
                const services = JSON.parse(leadData.service_type);
                serviceText = Array.isArray(services)
                    ? services.map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(', ')
                    : String(services);
            } catch (e) {
                serviceText = String(leadData.service_type);
            }
        }
        const serviceElement = templateContent.getElementById('leadModalService');
        if (serviceElement) serviceElement.textContent = serviceText;

        // Date
        const dateElement = templateContent.getElementById('leadModalDate');
        if (dateElement && leadData.created_at) {
            dateElement.textContent = formatDate(leadData.created_at);
        }

        // Email
        const emailElement = templateContent.getElementById('leadModalEmail');
        if (emailElement) {
            let email = 'N/A';
            if (clientInfo.emails && clientInfo.emails.length > 0) {
                email = clientInfo.emails[0];
            } else if (clientInfo.email) {
                email = clientInfo.email;
            }
            emailElement.textContent = email;
        }

        // Phone
        const phoneElement = templateContent.getElementById('leadModalPhone');
        if (phoneElement) {
            let phone = 'N/A';
            if (clientInfo.phones && clientInfo.phones.length > 0) {
                phone = clientInfo.phones[0];
            } else if (clientInfo.phone) {
                phone = clientInfo.phone;
            }
            phoneElement.textContent = phone;
        }

        // Country
        let country = 'N/A';
        let visaType = 'N/A';

        if (leadData.service_data) {
            try {
                const serviceData = JSON.parse(leadData.service_data);
                if (Array.isArray(serviceData) && serviceData.length > 0) {
                    const firstService = serviceData[0];
                    country = firstService.country ? firstService.country.toUpperCase() : 'N/A';
                    visaType = firstService.visaCategory ? firstService.visaCategory.toUpperCase() : 'N/A';
                }
            } catch (e) {
                console.error('Error parsing service_data:', e);
            }
        }

        const countryElement = templateContent.getElementById('leadModalCountry');
        if (countryElement) countryElement.textContent = country;

        const visaTypeElement = templateContent.getElementById('leadModalVisaType');
        if (visaTypeElement) visaTypeElement.textContent = visaType;

        // Additional Info
        const additionalInfoDiv = templateContent.getElementById('leadModalAdditionalInfo');
        if (additionalInfoDiv) {
            let html = '';
            if (leadData.additional_info && leadData.additional_info !== 'null') {
                try {
                    const additionalInfo = JSON.parse(leadData.additional_info);
                    for (const [key, value] of Object.entries(additionalInfo)) {
                        if (value && value !== 'null' && value !== '') {
                            html += `<div class="mb-2">
                                <p class="text-xs text-gray-500">${key.replace(/_/g, ' ').toUpperCase()}</p>
                                <p class="text-sm">${escapeHtml(String(value))}</p>
                            </div>`;
                        }
                    }
                } catch (e) {
                    html = `<p class="text-sm">${escapeHtml(String(leadData.additional_info))}</p>`;
                }
            }
            additionalInfoDiv.innerHTML = html || '<p class="text-gray-500 text-sm">No additional information available.</p>';
        }

        // Notes
        const notesElement = templateContent.getElementById('leadModalNotes');
        if (notesElement) {
            notesElement.value = leadData.notes || '';
        }

        // Set modal content
        modalTitle.textContent = 'Lead Details';
        modalContent.innerHTML = '';
        modalContent.appendChild(templateContent);

        // Store lead ID
        modalContent.dataset.leadId = leadData.id;
        currentLeadId = leadData.id;

        // Show modal
        modalOverlay.classList.remove('hidden');
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
            },
            inProgress: {
                workNo: 'WN-2023-051',
                client: 'Alex Johnson',
                required: 'Birth Certificate, Marriage Certificate',
                collected: '3',
                requiredDocs: '5',
                status: 'Pending Client'
            },
            submittedWork: {
                workNo: 'WN-2023-042',
                client: 'Maria Garcia',
                amendment: 'Additional identity proof',
                submittedTo: 'Global Visa Center',
                amendmentCount: '1',
                status: 'Under Review'
            },
            decidedToday: {
                title: 'Application Rejected',
                description: 'Samuel Lee - Business Visa',
                time: 'Today'
            },
            infoToProvide: {
                title: 'Employment Verification',
                description: 'Required for work permit application',
                time: 'Today',
                priority: 'High'
            },
            docCollection: {
                fromTo: 'From: Robert Brown',
                location: 'London, UK',
                concern: 'Home Office',
                phone: '+44 20 7946 0958',
                docType: 'Biometric Residence Permit',
                date: '20 Nov 2023'
            },
            docDelivery: {
                fromTo: 'To: US Embassy',
                location: 'London, UK',
                concern: 'Consular Section',
                phone: '+44 20 7499 9000',
                docType: 'Visa Application',
                date: '22 Nov 2023'
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

            case 'newWork':
                newCard = document.createElement('div');
                newCard.className = 'kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover';
                newCard.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-medium text-gray-800">${data.title}</h3>
                            <p class="text-sm text-gray-600 mt-1">${data.description}</p>
                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                <span>${data.time}</span>
                            </div>
                        </div>
                        <button class="kanban-btn-move text-gray-400 hover:text-blue-500" data-from="${columnId}" data-to="inProgress">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                `;
                break;

            case 'inProgress':
                newCard = document.createElement('div');
                newCard.className = 'kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover';
                newCard.innerHTML = `
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-medium text-gray-800">Work #${data.workNo}</h3>
                        <button class="kanban-btn-edit text-gray-400 hover:text-blue-500" data-card-type="inProgress">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Client:</span>
                                <span class="text-sm font-semibold">${data.client}</span>
                            </div>
                            <div class="mt-2">
                                <p class="text-xs text-gray-500">Required From Client:</p>
                                <p class="text-sm">${data.required}</p>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Collected:</span>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">${data.collected}/${data.requiredDocs}</span>
                            </div>
                            <div class="mt-2">
                                <p class="text-xs text-gray-500">Required Documents:</p>
                                <p class="text-sm">${data.requiredDocs} documents</p>
                            </div>
                            <div class="mt-1">
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">${data.status}</span>
                            </div>
                        </div>
                    </div>
                `;
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

    function openModal(modalId) {
        // This will be handled by UIInteractions.js
        // We only handle lead details modal here
        if (modalId === 'leadDetailsModal') {
            // This is called from UIInteractions
            return;
        }
    }

    function closeModal() {
        modalOverlay.classList.add('hidden');
        currentLeadId = null;
    }

    // Helper functions
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        } catch (e) {
            return 'Invalid Date';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function initEventListeners() {
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

        // Modal close button
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
    }

    function init() {
        // Load leads from API
        loadLeadsFromAPI();
        initEventListeners();
    }

    // Public API
    return {
        init: init,
        openLeadDetails: function (leadId) {
            const leadCard = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
            if (leadCard) {
                const leadData = JSON.parse(leadCard.dataset.leadInfo);
                openLeadDetailsModal(leadData);
            }
        },
        moveCard: moveCard,
        loadMoreItems: loadMoreItems,
        refreshLeads: function () {
            loadLeadsFromAPI();
        }
    };
})();