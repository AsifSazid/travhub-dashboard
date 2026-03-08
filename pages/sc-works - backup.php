<!-- Main Container -->
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Work Management</h1>
            <p class="text-gray-600 mt-2">Create and manage your work entries</p>
        </div>
        
        <!-- Form Container -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Create New Work</h2>
            <form action="" class="space-y-6">
                <!-- Form Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Work Title Field -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Work Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="work_title" placeholder="e.g., Website Redesign" 
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <p class="mt-1.5 text-xs text-gray-500">Give your work a descriptive title</p>
                    </div>
                    
                    <!-- Client Field -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Client <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <?php include('form-selects/clients.php') ?>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">Select client</p>
                    </div>
                    
                    <!-- Owned By Field -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Owned By <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <?php include('form-selects/employees.php') ?>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">Assign to team member</p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Work
                    </button>
                    <button type="reset" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="text-xl font-semibold text-gray-800">Work Lists</h2>
                    <div class="flex gap-2">
                        <input type="text" placeholder="Search works..." 
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="workTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Data will be populated here -->
                    </tbody>
                </table>
            </div>
            
            <!-- Table Footer with Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span class="font-medium">20</span> results</p>
                    <div class="flex gap-2">
                        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Previous</button>
                        <button class="px-3 py-1 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700">1</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">2</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">3</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript with improvements -->
<script>
    const API_URL_FOR_CLIENTS_WORKS = "<?php echo $getClientsWorksApi; ?>";
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    function loadWorks() {
        fetch(API_URL_FOR_CLIENTS_WORKS)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    showError('Failed to load works');
                    return;
                }
                renderTable(data.works);
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Something went wrong');
            });
    }

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

    function renderTable(list) {
        const tbody = document.getElementById('workTableBody');
        
        if (!list || list.length === 0) {
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
            return;
        }

        tbody.innerHTML = list.map(work => `
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
                    <div class="flex items-center gap-3">
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
    }

    // Load works when page loads
    document.addEventListener('DOMContentLoaded', loadWorks);
</script>

<!-- Custom Select Styling -->
<style>
    select {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.2rem;
        padding-right: 2.8rem;
        cursor: pointer;
    }
    
    input:focus, select:focus, button:focus {
        outline: none;
        ring: 2px solid rgba(139, 92, 246, 0.5);
    }
    
    /* Table hover effect */
    tbody tr {
        transition: all 0.2s ease;
    }
    
    /* Empty state styling */
    .empty-state svg {
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
    }
</style>