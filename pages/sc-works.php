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
                <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work Title</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
const API_URL_FOR_CLIENTS_WORKS = "<?php echo $getClientsWorksApi; ?>";
const CREATE_WORK_API = "<?php echo $createWorkApi; ?>?cid=<?php echo $_GET['cid']; ?>"; // Pass client ID as query param

// State management
let currentPage = 1;
let isLoading = false;
let hasMore = true;
let searchTerm = '';
let debounceTimer;

// Skeleton loader HTML
const skeletonLoader = `
    ${Array(5).fill(0).map(() => `
        <tr class="animate-pulse">
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-48"></div></td>
            <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded-full w-24"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
            <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-32 ml-auto"></div></td>
        </tr>
    `).join('')}
`;

// Format date function
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Show skeleton loader
function showSkeletonLoader() {
    document.getElementById('workTableBody').innerHTML = skeletonLoader;
}

// Create new work - Updated to match your backend
document.getElementById('createWorkForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    
    // Get the owned by value from select
    const ownedBySelect = document.querySelector('select[name="ownedBy"]');
    const ownedByValue = ownedBySelect ? ownedBySelect.value : '';
    
    // Prepare JSON data matching your backend structure
    const formData = {
        work_title: document.getElementById('work_title').value,
        ownedBy: ownedByValue
    };
    
    // Validate
    if (!formData.work_title) {
        alert('Please enter a work title');
        return;
    }
    if (!formData.ownedBy) {
        alert('Please select an owner');
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
    
    try {
        const response = await fetch(CREATE_WORK_API, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData) // Send as JSON, not FormData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reset form
            this.reset();
            
            // Reset pagination and reload
            currentPage = 1;
            hasMore = true;
            await loadWorks(true);
            
            // Show success message
            alert('Work created successfully!');
        } else {
            alert('Failed to create work: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Something went wrong. Please try again.');
    } finally {
        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Work
        `;
    }
});

// Debounced search
function debouncedSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        searchTerm = document.getElementById('searchInput').value;
        currentPage = 1;
        hasMore = true;
        loadWorks(true);
    }, 500);
}

// Load works with pagination - Updated to handle pagination params
async function loadWorks(reset = false) {
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    document.getElementById('loading-spinner').classList.remove('hidden');
    
    try {
        const url = new URL(API_URL_FOR_CLIENTS_WORKS);
        url.searchParams.append('page', currentPage);
        url.searchParams.append('search', searchTerm);
        url.searchParams.append('limit', 10);
        url.searchParams.append('cid', '<?php echo $_GET['cid']; ?>'); // Add client ID
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (!data.success) {
            showError('Failed to load works');
            return;
        }
        
        const tbody = document.getElementById('workTableBody');
        
        if (reset) {
            tbody.innerHTML = '';
        }
        
        if (data.works && data.works.length > 0) {
            renderTable(data.works, !reset);
            currentPage++;
            hasMore = data.has_more || false;
            
            // Update pagination info
            document.getElementById('pagination-info').textContent = 
                `Showing ${data.from || 1} to ${data.to || data.works.length} of ${data.total || 'many'} results`;
        } else {
            if (reset) {
                showEmptyState();
            }
            hasMore = false;
        }
    } catch (error) {
        console.error('Error:', error);
        if (reset) {
            showError('Something went wrong');
        }
    } finally {
        isLoading = false;
        document.getElementById('loading-spinner').classList.add('hidden');
    }
}

// Show error state
function showError(message) {
    const tbody = document.getElementById('workTableBody');
    tbody.innerHTML = `
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

// Show empty state
function showEmptyState() {
    const tbody = document.getElementById('workTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-lg font-medium text-gray-900 mb-1">No works found</p>
                <p class="text-sm">Create your first work using the form above</p>
            </td>
        </tr>
    `;
}

// Render table with right-aligned actions
function renderTable(works, append = false) {
    const tbody = document.getElementById('workTableBody');
    
    const rows = works.map(work => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900">${formatDate(work.created_at)}</span>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">${work.title || 'Untitled'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2.5 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">
                    ${work.client_name || 'Unassigned'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-600">${work.file_info || 'No files'}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center justify-end gap-3">
                    <a href="task-entry.php?work_id=${work.sys_id}" 
                       class="text-purple-600 hover:text-purple-900 p-2 hover:bg-purple-50 rounded-lg transition-colors"
                       title="View Tasks">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </a>
                    <button class="text-blue-600 hover:text-blue-900 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                            title="Edit Work"
                            onclick="editWork('${work.sys_id}')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors"
                            title="Delete Work"
                            onclick="deleteWork('${work.sys_id}')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    if (append) {
        tbody.insertAdjacentHTML('beforeend', rows);
    } else {
        tbody.innerHTML = rows;
    }
}

// Infinite scroll observer
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !isLoading && hasMore) {
            loadWorks();
        }
    });
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Show skeleton loader initially
    showSkeletonLoader();
    
    // Load first page
    loadWorks(true);
    
    // Setup infinite scroll
    const sentinel = document.getElementById('scroll-sentinel');
    if (sentinel) {
        observer.observe(sentinel);
    }
    
    // Setup search with debounce
    document.getElementById('searchInput').addEventListener('input', debouncedSearch);
});

// Cleanup observer on page unload
window.addEventListener('beforeunload', () => {
    observer.disconnect();
});

// Placeholder functions for edit and delete
function editWork(id) {
    console.log('Edit work:', id);
    // Implement edit functionality
}

function deleteWork(id) {
    if (confirm('Are you sure you want to delete this work?')) {
        console.log('Delete work:', id);
        // Implement delete functionality
    }
}
</script>

<style>
/* Existing styles plus new additions */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Smooth transitions */
tbody tr {
    transition: all 0.2s ease;
}

/* Loading spinner animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Right alignment for actions */
th:last-child, td:last-child {
    text-align: right;
}

td:last-child .flex {
    justify-content: flex-end;
}
</style>