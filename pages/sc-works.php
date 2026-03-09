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
const API_URL_FOR_CLIENTS_WORKS = "<?php echo $getClientsWorksApi; ?>";
const STORE_WORK_API = "<?php echo $storeWorkApi; ?>"; // Pass client ID as query param
let eclient = null;
let clientName = null;

const GET_CLIENT_INFO_API = "<?php echo $getClient; ?>";

async function fetchClientData() {
    try {
        const response = await fetch(GET_CLIENT_INFO_API);
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        
        // eclient = data.client;
        clientName = data.client.name;

    } catch (error) {
        console.error('There was a problem with the fetch operation:', error);
    }
}

// Function-ti call kora hocche
fetchClientData();


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
// Store work - Updated to match your form structure
document.getElementById('createWorkForm').addEventListener('submit', function(e) {
    e.preventDefault(); // page reload prevent
    
    console.log('Form submitted'); // Debug log
    
    const submitBtn = document.getElementById('submitBtn');
    
    // Get form values
    const workTitle = document.getElementById('work_title').value;
    const ownedByValue = document.getElementById('employeeInput').value;
    const clientValue = `<?php echo $clientId ?> | ${clientName}`;
    
    console.log('Form values:', { workTitle, ownedByValue, clientValue }); // Debug log
    
    // Validate
    if (!workTitle) {
        alert('Please enter a work title');
        return;
    }
    if (!ownedByValue) {
        alert('Please select an owner');
        return;
    }
    if (!clientValue) {
        alert('Client ID is missing');
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
    
    // Prepare payload
    const payload = {
        client: clientValue,
        ownedBy: ownedByValue,
        work_title: workTitle
    };
    
    console.log('Sending payload:', payload); // Debug log
    console.log('API URL:', STORE_WORK_API); // Debug log
    
    // Send data to API
    fetch(STORE_WORK_API, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => {
        console.log('Response status:', res.status); // Debug log
        return res.json();
    })
    .then(data => {
        // console.log('API Response:', data);
        
        if (data.success) {
            alert('Work saved successfully!');
            
            // Reset form
            document.getElementById('createWorkForm').reset();
            
            // Reset pagination and reload works
            currentPage = 1;
            hasMore = true;
            loadWorks(true);
        } else {
            alert('Error: ' + (data.message || 'Something went wrong'));
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Network or server error: ' + err.message);
    })
    .finally(() => {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Work
        `;
    });
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
        url.searchParams.append('cid', '<?php echo $clientId; ?>'); // Add client ID
        
        // console.log('Fetching URL:', url.toString()); // Debug: Check the URL
        
        const response = await fetch(url);
        const data = await response.json();
        
        // console.log('API Response:', data); // Debug: See what the API returns
        
        // Check if data exists
        if (!data) {
            console.error('No data received from API');
            showError('No data received from server');
            return;
        }
        
        // Check if API returned success (adjust based on your actual API response)
        if (data.success === false) {
            showError(data.message || 'Failed to load works');
            return;
        }
        
        const tbody = document.getElementById('workTableBody');
        
        if (reset) {
            tbody.innerHTML = '';
        }
        
        // Determine where the works array is in the response
        // This is the key part - adjust based on your API response structure
        let works = [];
        let hasMorePages = false;
        let totalCount = 0;
        let fromCount = 1;
        let toCount = 0;
        
        // Check different possible response structures
        if (data.works && Array.isArray(data.works)) {
            // Structure 1: { works: [...], has_more: true }
            works = data.works;
            hasMorePages = data.has_more || false;
            totalCount = data.total || works.length;
            fromCount = data.from || 1;
            toCount = data.to || works.length;
        } 
        else if (data.data && Array.isArray(data.data)) {
            // Structure 2: { data: [...], next_page_url: '...' }
            works = data.data;
            hasMorePages = !!data.next_page_url;
            totalCount = data.total || works.length;
            fromCount = data.from || 1;
            toCount = data.to || works.length;
        }
        else if (Array.isArray(data)) {
            // Structure 3: Direct array [...]
            works = data;
            hasMorePages = false; // Can't determine if more exist
            totalCount = works.length;
            fromCount = 1;
            toCount = works.length;
        }
        else if (data.results && Array.isArray(data.results)) {
            // Structure 4: { results: [...], total: 100 }
            works = data.results;
            hasMorePages = data.has_more || (data.current_page < data.last_page);
            totalCount = data.total || works.length;
            fromCount = data.from || ((data.current_page - 1) * data.per_page + 1);
            toCount = data.to || (fromCount + works.length - 1);
        }
        
        // console.log('Extracted works:', works); // Debug: See what we got
        
        if (works && works.length > 0) {
            renderTable(works, !reset);
            currentPage++;
            hasMore = hasMorePages;
            
            // Update pagination info
            const paginationInfo = document.getElementById('pagination-info');
            if (totalCount > 0) {
                paginationInfo.textContent = 
                    `Showing ${fromCount} to ${toCount} of ${totalCount} results`;
            } else {
                paginationInfo.textContent = 
                    `Showing ${works.length} results`;
            }
        } else {
            if (reset) {
                showEmptyState();
            }
            hasMore = false;
            document.getElementById('pagination-info').textContent = 'No results found';
        }
    } catch (error) {
        console.error('Error loading works:', error);
        if (reset) {
            showError('Something went wrong: ' + error.message);
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
    
    const rows = works.map(work => {
        // Debug individual work object
        // console.log('Work object:', work);
        
        // Map field names based on your API response
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
    if (window.workObserver) {
        window.workObserver.disconnect();
    }
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