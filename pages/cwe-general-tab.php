<div id="generalTab" class="tab-content">
    <div class="flex items-start gap-4 flex-wrap">
        <!-- Left content -->
        <div class="flex-1 min-w-0">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">
                General Information for Completed Work Entry
            </h2>
            <p class="text-sm text-gray-600 mb-4">Please fill up the form</p>
        </div>

        <!-- Button only on md and above -->
        <a href="create-client.php" class="hidden md:flex w-48 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-md rounded-lg shadow-md hover:shadow-lg transition-all duration-300 items-center justify-center">
            <i class="fas fa-plus-circle mr-3"></i>Add New Client
        </a>
    </div>

    <form action="">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="col-span-3 block text-sm font-medium text-gray-700 my-2">Search Client</label>

                <div class="relative w-full">
                    <div class="flex">
                        <input
                            type="text"
                            id="clientInput"
                            placeholder="Search for a client..."
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
                            autocomplete="off">
                        <button
                            id="dropdownToggle"
                            class="px-4 py-2 border border-gray-300 border-l-0 rounded-r-lg bg-gray-100 hover:bg-gray-200"
                            type="button">
                            ▼
                        </button>
                    </div>
                    <ul id="clientDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto hidden z-50">
                        <!-- JS will populate options here -->
                    </ul>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 my-2">Work Title</label>
                <input name="work_title" placeholder="Write a Work Title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <button type="submit" class="flex-1 px-4 py-2 mt-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">Submit</button>
    </form>

    <hr class="my-6">

    <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <h2 class="text-2xl font-semibold text-gray-800">Work Lists</h2>

            <!-- Search Area -->
            <div id="work-search" class="w-full md:w-auto">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input
                        type="text"
                        id="workSearchInput"
                        placeholder="Search works by title, client, files, or creator..."
                        class="pl-10 pr-4 py-2.5 w-full md:w-80 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition-all duration-200">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span id="workSearchResultCount" class="text-xs text-gray-400 hidden">0 found</span>
                    </div>
                </div>

                <!-- Search Filters -->
                <div class="mt-2 flex flex-wrap gap-2">
                    <div class="flex items-center space-x-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="workSearchFilter" value="title" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Title</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="workSearchFilter" value="client" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Client</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="workSearchFilter" value="files" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Files</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="workSearchFilter" value="creator" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Creator</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="workSearchFilter" value="updater" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Updater</span>
                        </label>
                    </div>

                    <button id="clearWorkSearch" class="ml-2 px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors">
                        <i class="fas fa-times mr-1"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="workLoadingIndicator" class="hidden flex items-center justify-center p-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600">Loading works...</span>
        </div>

        <!-- No Results Message -->
        <div id="workNoResultsMessage" class="hidden text-center p-8">
            <i class="fas fa-search text-gray-300 text-4xl mb-3"></i>
            <h3 class="text-lg font-medium text-gray-700 mb-1">No works found</h3>
            <p class="text-gray-500">Try adjusting your search or filters</p>
        </div>

        <!-- Works Container -->
        <div class="overflow-x-auto table-container">
            <div id="worksContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 p-4">
                <!-- Works cards will be dynamically inserted here -->
            </div>
        </div>

        <!-- Results Info -->
        <div id="workResultsInfo" class="mt-4 pt-4 border-t border-gray-200 hidden">
            <div class="flex items-center justify-between text-sm text-gray-600">
                <span id="totalWorksCount">0 works</span>
                <span id="filteredWorksCount" class="font-medium"></span>
            </div>
        </div>
    </div>
</div>

<script>
    // All API's for this Page
    const API_URL_FOR_ALL_CLIENTS = "<?php echo $getAllClientsApi; ?>";
    const API_URL_FOR_ALL_WORKS = "<?php echo $getAllWorksApi; ?>";
    const API_URL_FOR_WORK_STORE = "<?php echo $storeWorkApi; ?>";


    // get client's data
    const clientInput = document.getElementById('clientInput');
    const clientDropdown = document.getElementById('clientDropdown');
    const dropdownToggle = document.getElementById('dropdownToggle');

    let clientsData = [];

    fetch(API_URL_FOR_ALL_CLIENTS)
        .then(res => res.json())
        .then(data => {
            clientsData = data.clients;
            renderDropdown(clientsData);
        })
        .catch(err => console.error(err));

    function renderDropdown(list) {
        clientDropdown.innerHTML = '';
        list.forEach(client => {
            // phone ke parse koro
            const phoneObj = JSON.parse(client.phone);
            const primaryPhone = phoneObj.primary_no;

            const li = document.createElement('li');
            li.textContent = `${client.id} | ${client.name} | ${primaryPhone}`;
            li.className = "px-4 py-2 cursor-pointer hover:bg-purple-100";
            li.addEventListener('click', () => {
                clientInput.value = li.textContent + ` | ${client.sys_id}`;
                clientDropdown.classList.add('hidden');
            });
            clientDropdown.appendChild(li);
        });
    }

    // Filter on typing
    clientInput.addEventListener('input', () => {
        const value = clientInput.value.toLowerCase();
        const filtered = clientsData.filter(c =>
            `${c.id} | ${c.name} | ${c.phone}`.toLowerCase().includes(value)
        );
        renderDropdown(filtered);
        clientDropdown.classList.remove('hidden');
    });

    // Toggle button click
    dropdownToggle.addEventListener('click', () => {
        if (clientDropdown.classList.contains('hidden')) {
            renderDropdown(clientsData);
            clientDropdown.classList.remove('hidden');
        } else {
            clientDropdown.classList.add('hidden');
        }
    });

    // Hide dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!clientInput.contains(e.target) && !clientDropdown.contains(e.target) && !dropdownToggle.contains(e.target)) {
            clientDropdown.classList.add('hidden');
        }
    });



    // get all works
    // Global variables for works
    let allWorks = [];
    let workSearchTimeout;

    // Get DOM elements
    const worksContainer = document.getElementById('worksContainer');
    const workSearchInput = document.getElementById('workSearchInput');
    const workSearchResultCount = document.getElementById('workSearchResultCount');
    const workLoadingIndicator = document.getElementById('workLoadingIndicator');
    const workNoResultsMessage = document.getElementById('workNoResultsMessage');
    const workResultsInfo = document.getElementById('workResultsInfo');
    const totalWorksCount = document.getElementById('totalWorksCount');
    const filteredWorksCount = document.getElementById('filteredWorksCount');
    const clearWorkSearchBtn = document.getElementById('clearWorkSearch');

    function loadWorks() {
        workLoadingIndicator.classList.remove('hidden');
        worksContainer.innerHTML = '';

        fetch(API_URL_FOR_ALL_WORKS)
            .then(res => res.json())
            .then(data => {
                allWorks = data.works || [];
                renderWorkCards(allWorks);
                updateWorkResultsInfo(allWorks.length, allWorks.length);
                workLoadingIndicator.classList.add('hidden');
            })
            .catch(err => {
                console.error('Error fetching works:', err);
                workLoadingIndicator.classList.add('hidden');
            });
    }

    function renderWorkCards(works) {
        // Clear previous data
        worksContainer.innerHTML = '';

        if (works.length === 0) {
            workNoResultsMessage.classList.remove('hidden');
            worksContainer.classList.add('hidden');
            return;
        }

        workNoResultsMessage.classList.add('hidden');
        worksContainer.classList.remove('hidden');

        works.forEach(work => {
            const meta = work.meta_data ? JSON.parse(work.meta_data) : {};
            const created = meta.created_by_date || {};
            const updatedArray = meta.updated_by_date || [];
            const lastUpdate = updatedArray.length > 0 ? updatedArray[updatedArray.length - 1] : null;

            const card = document.createElement('a');
            card.href = `task-entry.php?work_id=${work.sys_id}`;
            card.className = "group bg-white rounded-lg shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-200 overflow-hidden flex flex-col h-full hover:-translate-y-1 hover:border-blue-300 cursor-pointer";

            card.innerHTML = `
            <div class="p-4 flex-grow">
                <!-- Title and client -->
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-gray-900 truncate group-hover:text-blue-600 transition-colors mb-2 work-title" title="${work.title || 'No Title'}">
                        ${work.title || 'No Title'}
                    </h3>
                    <div class="flex items-center work-client">
                        <i class="fas fa-user-tie text-gray-400 mr-2 text-sm"></i>
                        <span class="text-sm text-gray-700 truncate">${work.client_name || 'Unknown'}</span>
                    </div>
                </div>

                <!-- Files info -->
                <div class="mb-4 flex items-center text-gray-600 bg-gray-50 rounded-lg p-3 work-files">
                    <i class="fas fa-folder text-gray-400 mr-3"></i>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm truncate block">${work.file_info || 'Folder'}</span>
                    </div>
                </div>

                <!-- Creation and Update side by side -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Created Section -->
                    <div class="bg-blue-50 rounded-lg p-3 border border-blue-100 work-creator">
                        <div class="flex items-center mb-2">
                            <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                <i class="fas fa-plus text-blue-600 text-xs"></i>
                            </div>
                            <span class="text-xs font-semibold text-blue-800 uppercase">Created</span>
                        </div>
                        <div class="text-center">
                            <div class="font-semibold text-gray-900 text-sm mb-1 truncate capitalize">${created.user || 'N/A'}</div>
                            <div class="text-xs text-gray-600">${created.date || ''}</div>
                        </div>
                    </div>

                    <!-- Updated Section -->
                    <div class="${lastUpdate ? 'bg-green-50 rounded-lg p-3 border border-green-100 work-updater' : 'bg-gray-50 rounded-lg p-3 border border-gray-100'}">
                        <div class="flex items-center mb-2">
                            <div class="w-7 h-7 rounded-full ${lastUpdate ? 'bg-green-100' : 'bg-gray-100'} flex items-center justify-center mr-2">
                                <i class="fas fa-sync-alt ${lastUpdate ? 'text-green-600' : 'text-gray-400'} text-xs"></i>
                            </div>
                            <span class="text-xs font-semibold ${lastUpdate ? 'text-green-800' : 'text-gray-500'} uppercase">${lastUpdate ? 'Updated' : 'No Update'}</span>
                        </div>
                        <div class="text-center">
                            ${lastUpdate ? `
                                <div class="font-semibold text-gray-900 text-sm mb-1 truncate capitalize">${lastUpdate.user}</div>
                                <div class="text-xs text-gray-600">${lastUpdate.date}</div>
                            ` : `
                                <div class="font-semibold text-gray-400 text-sm mb-1">N/A</div>
                                <div class="text-xs text-gray-400">-</div>
                            `}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer with task icon -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 group-hover:bg-blue-50 transition-colors">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 group-hover:text-blue-700 transition-colors">
                        View Tasks
                    </span>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-blue-500 group-hover:translate-x-1 transition-all"></i>
                </div>
            </div>
        `;

            worksContainer.appendChild(card);
        });
    }

    function searchWorks() {
        const searchTerm = workSearchInput.value.toLowerCase().trim();
        const filters = Array.from(document.querySelectorAll('input[name="workSearchFilter"]:checked'))
            .map(checkbox => checkbox.value);

        if (searchTerm === '') {
            renderWorkCards(allWorks);
            updateWorkResultsInfo(allWorks.length, allWorks.length);
            workSearchResultCount.classList.add('hidden');
            return;
        }

        const filteredWorks = allWorks.filter(work => {
            let match = false;

            // Parse meta data for search
            const meta = work.meta_data ? JSON.parse(work.meta_data) : {};
            const created = meta.created_by_date || {};
            const updatedArray = meta.updated_by_date || [];
            const lastUpdate = updatedArray.length > 0 ? updatedArray[updatedArray.length - 1] : null;

            filters.forEach(filter => {
                switch (filter) {
                    case 'title':
                        if (work.title && work.title.toLowerCase().includes(searchTerm)) match = true;
                        break;
                    case 'client':
                        if (work.client_name && work.client_name.toLowerCase().includes(searchTerm)) match = true;
                        break;
                    case 'files':
                        if (work.file_info && work.file_info.toLowerCase().includes(searchTerm)) match = true;
                        break;
                    case 'creator':
                        if (created.user && created.user.toLowerCase().includes(searchTerm)) match = true;
                        break;
                    case 'updater':
                        if (lastUpdate && lastUpdate.user && lastUpdate.user.toLowerCase().includes(searchTerm)) match = true;
                        break;
                }
            });

            return match;
        });

        renderWorkCards(filteredWorks);
        updateWorkResultsInfo(allWorks.length, filteredWorks.length);

        // Update search result count
        if (filteredWorks.length > 0) {
            workSearchResultCount.textContent = `${filteredWorks.length} found`;
            workSearchResultCount.classList.remove('hidden');
        } else {
            workSearchResultCount.classList.add('hidden');
        }
    }

    function updateWorkResultsInfo(total, filtered) {
        totalWorksCount.textContent = `${total} works`;

        if (total === filtered) {
            workResultsInfo.classList.add('hidden');
        } else {
            workResultsInfo.classList.remove('hidden');
            filteredWorksCount.textContent = `Showing ${filtered} of ${total}`;
        }
    }

    // Event Listeners for works search
    workSearchInput.addEventListener('input', () => {
        clearTimeout(workSearchTimeout);
        workSearchTimeout = setTimeout(searchWorks, 300);
    });

    // Add keydown event for instant search on Enter
    workSearchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            clearTimeout(workSearchTimeout);
            searchWorks();
        }
    });

    // Filter checkbox change event for works
    document.querySelectorAll('input[name="workSearchFilter"]').forEach(checkbox => {
        checkbox.addEventListener('change', searchWorks);
    });

    // Clear search button for works
    clearWorkSearchBtn.addEventListener('click', () => {
        workSearchInput.value = '';
        workSearchResultCount.classList.add('hidden');
        renderWorkCards(allWorks);
        updateWorkResultsInfo(allWorks.length, allWorks.length);
    });


    document.addEventListener('DOMContentLoaded', loadWorks);

    // store work
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // page reload prevent

        // Get form values
        const clientValue = document.getElementById('clientInput').value;
        const workTitle = form.querySelector('input[name="work_title"]').value;

        // Prepare payload
        const payload = {
            client: clientValue,
            work_title: workTitle
        };

        // Send data to API
        fetch(API_URL_FOR_WORK_STORE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                console.log('API Response:', data);

                if (data.success) {
                    alert('Work saved successfully!');
                    form.reset(); // optional: reset form
                    loadWorks();
                } else {
                    alert('Error: ' + (data.message || 'Something went wrong'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Network or server error');
            });
    });
</script>