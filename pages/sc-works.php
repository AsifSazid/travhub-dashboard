<!-- Form Container -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Create New Work</h2>
    <form id="createWorkForm" class="space-y-6">
        <!-- Form Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Work Title Field -->
            <div class="text-left">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Work Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="work_title" id="work_title" placeholder="e.g., Honeymoon Tour Package" 
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                <p class="mt-1.5 text-xs text-gray-500">Give your work a descriptive title</p>
            </div>
            
            <!-- Owned By Field -->
            <div class="text-left">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Owned By <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <?php include('form-selects/employees.php') ?>
                </div>
                <p class="mt-1.5 text-xs text-gray-500">Assign to team member</p>
            </div>
            
            <div class="justify-items-end">
                <!-- Form Actions -->
                <div class="flex flex-wrap gap-3 pl-4 pt-7 border-l border-gray-200">
                    <button type="submit" id="submitBtn" class="inline-flex items-center px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Work
                    </button>
                    <button type="reset" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Table Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-5 border-b border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Work Lists</h2>
            <div class="flex gap-2">
                <input type="text" id="searchInput" placeholder="Search works..." 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 w-64">
                <button id="searchButton" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-center">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Work Title</th>
                    <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                    <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="workTableBody" class="bg-white divide-y divide-gray-200">
                <!-- Skeleton loader will be shown here initially -->
            </tbody>
        </table>
    </div>
    
    <!-- Infinite scroll sentinel -->
    <div id="scroll-sentinel" class="h-4 w-full"></div>
    
    <!-- Table Footer with Pagination Info -->
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p id="pagination-info" class="text-sm text-gray-600">Loading...</p>
            <div id="loading-spinner" class="hidden">
                <svg class="animate-spin h-5 w-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<script>
    // API URLs
    const API_URL_FOR_CLIENTS_WORKS = "<?php echo $getClientsWorksApi; ?>";
    const STORE_WORK_API = "<?php echo $storeWorkApi; ?>";
    
    // Global variables
    let allWorks = [];           // All works fetched from API
    let filteredWorks = [];      // Filtered works based on search
    let currentPage = 1;
    let itemsPerPage = 10;
    let isLoading = false;
    let hasMore = true;
    let searchTerm = '';
    let debounceTimer;
    let observer;
    
    // Skeleton loader HTML
    const skeletonLoader = `
        ${Array(5).fill(0).map(() => `
            <tr class="animate-pulse">
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24 mx-auto"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-48 mx-auto"></div></td>
                <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded-full w-24 mx-auto"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32 mx-auto"></div></td>
                <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-32 mx-auto"></div></td>
            </tr>
        `).join('')}
    `;
    
    // ==================== HELPER FUNCTIONS ====================
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    function showSkeletonLoader() {
        document.getElementById('workTableBody').innerHTML = skeletonLoader;
    }
    
    
    // ==================== FETCH ALL WORKS ====================
    
    async function fetchAllWorks() {
        showSkeletonLoader();
        
        try {
            const url = new URL(API_URL_FOR_CLIENTS_WORKS);
            url.searchParams.append('cid', '<?php echo $clientId; ?>');
            url.searchParams.append('get_all', 'true'); // Optional: if your API supports getting all
            
            console.log('Fetching all works:', url.toString());
            
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            console.log('All works received:', data);
            
            // Store all works
            if (data.works && Array.isArray(data.works)) {
                allWorks = data.works;
            } else if (data.data && Array.isArray(data.data)) {
                allWorks = data.data;
            } else if (Array.isArray(data)) {
                allWorks = data;
            } else {
                allWorks = [];
            }
            
            // Initialize filtered works with all works
            filteredWorks = [...allWorks];
            
            // Reset pagination
            currentPage = 1;
            hasMore = filteredWorks.length > itemsPerPage;
            
            // Render first page
            renderCurrentPage();
            
            // Update pagination info
            updatePaginationInfo();
            
        } catch (error) {
            console.error('Error fetching works:', error);
            showError('Failed to load works: ' + error.message);
        }
    }
    
    // ==================== SEARCH FUNCTIONALITY ====================
    
    function performSearch() {
        searchTerm = document.getElementById('searchInput').value.trim().toLowerCase();
        console.log('Searching locally for:', searchTerm);
        
        if (searchTerm === '') {
            // If search is empty, show all works
            filteredWorks = [...allWorks];
        } else {
            // Filter works locally
            filteredWorks = allWorks.filter(work => {
                const title = (work.title || work.work_title || work.name || '').toLowerCase();
                const client = (work.client_name || work.client || work.customer || '').toLowerCase();
                return title.includes(searchTerm) || client.includes(searchTerm);
            });
        }
        
        // console.log(`Found ${filteredWorks.length} results`);
        
        // Reset pagination
        currentPage = 1;
        hasMore = filteredWorks.length > itemsPerPage;
        
        // Render first page of filtered results
        renderCurrentPage();
        
        // Update pagination info
        updatePaginationInfo();
        
        // Show message if no results
        if (filteredWorks.length === 0) {
            showNoSearchResults(searchTerm);
        }
    }
    
    function debouncedSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            performSearch();
        }, 500);
    }
    
    // ==================== PAGINATION FUNCTIONS ====================
    
    function renderCurrentPage() {
        const tbody = document.getElementById('workTableBody');
        
        // Calculate start and end indices
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        
        // Get current page items
        const currentItems = filteredWorks.slice(start, end);
        
        if (currentItems.length === 0 && filteredWorks.length > 0) {
            // This should not happen normally, but just in case
            currentPage = Math.max(1, Math.ceil(filteredWorks.length / itemsPerPage));
            renderCurrentPage();
            return;
        }
        
        // Render the items
        renderTable(currentItems, false);
        
        // Update hasMore for infinite scroll
        hasMore = end < filteredWorks.length;
    }
    
    function loadNextPage() {
        if (isLoading || !hasMore) return;
        
        isLoading = true;
        document.getElementById('loading-spinner').classList.remove('hidden');
        
        // Simulate loading delay (remove in production)
        setTimeout(() => {
            currentPage++;
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const nextItems = filteredWorks.slice(start, end);
            
            if (nextItems.length > 0) {
                appendTable(nextItems);
            }
            
            hasMore = end < filteredWorks.length;
            isLoading = false;
            document.getElementById('loading-spinner').classList.add('hidden');
            
            updatePaginationInfo();
        }, 500); // Remove this setTimeout in production
    }
    
    function appendTable(items) {
        const tbody = document.getElementById('workTableBody');
        const rows = generateTableRows(items);
        tbody.insertAdjacentHTML('beforeend', rows);
    }
    
    function updatePaginationInfo() {
        const start = (currentPage - 1) * itemsPerPage + 1;
        const end = Math.min(currentPage * itemsPerPage, filteredWorks.length);
        const total = filteredWorks.length;
        
        const paginationInfo = document.getElementById('pagination-info');
        
        if (total === 0) {
            paginationInfo.textContent = searchTerm ? 
                `No results found for "${searchTerm}"` : 'No works found';
        } else {
            paginationInfo.textContent = `Showing ${start} to ${end} of ${total} results${searchTerm ? ` for "${searchTerm}"` : ''}`;
        }
    }
    
    // ==================== RENDER FUNCTIONS ====================
    
    function generateTableRows(works) {
        return works.map(work => {
            const title = work.title || work.work_title || work.name || 'Untitled';
            const createdAt = work.created_at || work.createdAt || work.date || null;
            const clientName = work.client_name || work.client || work.customer || 'Unassigned';
            const fileInfo = work.file_info || work.files || work.file_count || 'No files';
            const sysId = work.sys_id || work.id || work.work_id;
            
            return `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-medium text-gray-900">${formatDate(createdAt)}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">${title}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2.5 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">
                        ${clientName}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-gray-600">${fileInfo}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-end gap-3">
                        <a href="task-entry.php?work_id=${sysId}" 
                           class="text-purple-600 hover:text-purple-900 p-2 hover:bg-purple-50 rounded-lg transition-colors"
                           title="View Tasks">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </a>
                        <button class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                                title="Edit Work"
                                onclick="editWork('${sysId}')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
                                title="Delete Work"
                                onclick="deleteWork('${sysId}')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `}).join('');
    }
    
    function renderTable(works, append = false) {
        const tbody = document.getElementById('workTableBody');
        const rows = generateTableRows(works);
        
        if (append) {
            tbody.insertAdjacentHTML('beforeend', rows);
        } else {
            tbody.innerHTML = rows;
        }
    }
    
    // ==================== CREATE WORK ====================
    
    document.getElementById('createWorkForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const workTitle = document.getElementById('work_title').value;
        const ownedByValue = document.getElementById('employeeInput').value;
        const clientValue = `<?php echo $clientId ?> | ${clientName}`;
        
        if (!workTitle || !ownedByValue) {
            alert('Please fill all required fields');
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Creating...
        `;
        
        const payload = {
            client: clientValue,
            ownedBy: ownedByValue,
            work_title: workTitle
        };
        
        fetch(STORE_WORK_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Work saved successfully!');
                document.getElementById('createWorkForm').reset();
                
                // Add the new work to allWorks array
                if (data.work) {
                    // If API returns the created work
                    allWorks.unshift(data.work); // Add to beginning
                } else {
                    // If API doesn't return the work, refetch all works
                    fetchAllWorks();
                    return;
                }
                
                // Update filtered works based on current search
                if (searchTerm) {
                    performSearch();
                } else {
                    filteredWorks = [...allWorks];
                    currentPage = 1;
                    renderCurrentPage();
                    updatePaginationInfo();
                }
            } else {
                alert('Error: ' + (data.message || 'Something went wrong'));
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Network or server error: ' + err.message);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Work
            `;
        });
    });
    
    // ==================== UI STATE FUNCTIONS ====================
    
    function showError(message) {
        document.getElementById('workTableBody').innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-lg font-medium text-gray-900 mb-1">${message}</p>
                    <p class="text-sm">Please try again later</p>
                </td>
            </tr>
        `;
    }
    
    function showNoSearchResults(searchTerm) {
        document.getElementById('workTableBody').innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p class="text-lg font-medium text-gray-900 mb-1">No results found</p>
                    <p class="text-sm">No works matching "${searchTerm}"</p>
                    <button onclick="clearSearch()" class="mt-4 px-4 py-2 text-sm text-purple-600 hover:text-purple-700 font-medium">
                        Clear search
                    </button>
                </td>
            </tr>
        `;
    }
    
    function clearSearch() {
        document.getElementById('searchInput').value = '';
        searchTerm = '';
        filteredWorks = [...allWorks];
        currentPage = 1;
        hasMore = filteredWorks.length > itemsPerPage;
        renderCurrentPage();
        updatePaginationInfo();
    }
    
    // ==================== ACTION FUNCTIONS ====================
    
    function editWork(id) {
        console.log('Edit work:', id);
        // Implement edit functionality
    }
    
    function deleteWork(id) {
        if (confirm('Are you sure you want to delete this work?')) {
            console.log('Delete work:', id);
            // Implement delete functionality
            // After delete, remove from allWorks and update UI
        }
    }
    
    // ==================== INITIALIZATION ====================
    
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM loaded, initializing...');
        
        // Fetch all works
        fetchAllWorks();
        
        // Setup infinite scroll
        observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !isLoading && hasMore) {
                    loadNextPage();
                }
            });
        });
        
        const sentinel = document.getElementById('scroll-sentinel');
        if (sentinel) {
            observer.observe(sentinel);
        }
        
        // Setup search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', debouncedSearch);
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    performSearch();
                }
            });
        }
        
        // Setup search button
        const searchButton = document.getElementById('searchButton');
        if (searchButton) {
            searchButton.addEventListener('click', (e) => {
                e.preventDefault();
                performSearch();
            });
        }
    });
    
    // Cleanup
    window.addEventListener('beforeunload', () => {
        if (observer) {
            observer.disconnect();
        }
    });
</script>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Table styles */
tbody tr {
    transition: all 0.2s ease;
}

th:last-child, td:last-child {
    text-align: right;
}

td:last-child .flex {
    justify-content: flex-end;
}

/* Search input focus */
#searchInput:focus {
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    outline: none;
}

/* No results animation */
td[colspan="5"] {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Center skeleton loader content */
.animate-pulse td div {
    margin-left: auto;
    margin-right: auto;
}
</style>