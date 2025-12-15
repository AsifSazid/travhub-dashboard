<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Service Lead Generation</title>
    <link rel="icon" type="image/png" href="./assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .search-result-item {
            transition: all 0.2s ease;
        }

        .search-result-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .drag-drop-area {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }

        .drag-drop-area.dragover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }

        .file-item {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .draggable-item {
            cursor: move;
            user-select: none;
        }

        .draggable-item.dragging {
            opacity: 0.5;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include 'elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include 'elements/aside.php'; ?>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <div class="grid grid-cols-6 gap-4">

                <!-- Left Column: Client Search -->
                <div class="col-span-3 bg-white rounded-lg shadow p-4 flex flex-col h-[calc(50vh-4rem)]">
                    <!-- Header -->
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-user-friends mr-2 text-primary-600"></i>
                            Client Search
                        </h2>
                        <p class="text-sm text-gray-600">Search clients by various criteria</p>
                    </div>

                    <!-- Search Form -->
                    <div class="mb-4">
                        <div class="flex space-x-2">
                            <select id="clientSearchType" class="w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="name">Name</option>
                                <option value="phone">Phone</option>
                                <option value="email">Email</option>
                                <option value="company">Company</option>
                                <option value="id">ID</option>
                                <option value="position">Position</option>
                                <option value="work_name">Work Name</option>
                                <option value="vendor_status">Vendor Status</option>
                                <option value="phone2">Phone 2</option>
                            </select>
                            <input type="text" id="clientSearchInput" placeholder="Enter search term..." class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <button id="clientSearchBtn" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Results Container -->
                    <div id="clientResults" class="flex-1 overflow-y-auto border border-gray-200 rounded-lg p-2 bg-gray-50">
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p>Search results will appear here</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Vendor Search -->
                <div class="col-span-3 bg-white rounded-lg shadow p-4 flex flex-col h-[calc(50vh-4rem)]">
                    <!-- Header -->
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-building mr-2 text-green-600"></i>
                            Vendor Search
                        </h2>
                        <p class="text-sm text-gray-600">Search vendors by various criteria</p>
                    </div>

                    <!-- Search Form -->
                    <div class="mb-4">
                        <div class="flex space-x-2">
                            <select id="vendorSearchType" class="w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="name">Name</option>
                                <option value="phone">Phone</option>
                                <option value="email">Email</option>
                                <option value="company">Company</option>
                                <option value="id">ID</option>
                                <option value="position">Position</option>
                                <option value="work_name">Work Name</option>
                                <option value="vendor_status">Vendor Status</option>
                                <option value="phone2">Phone 2</option>
                            </select>
                            <input type="text" id="vendorSearchInput" placeholder="Enter search term..." class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <button id="vendorSearchBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Results Container -->
                    <div id="vendorResults" class="flex-1 overflow-y-auto border border-gray-200 rounded-lg p-2 bg-gray-50">
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p>Search results will appear here</p>
                        </div>
                    </div>
                </div>

                <!-- Full Column: Drag & Drop and Paste Area -->
                <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col h-[calc(50vh-4rem)]">
                    <!-- Header -->
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-file-import mr-2 text-purple-600"></i>
                            File Management
                        </h2>
                        <p class="text-sm text-gray-600">Drag & drop files or paste content from clipboard</p>
                    </div>

                    <!-- Two Column Layout for Drag & Drop -->
                    <div class="flex-1 grid grid-cols-2 gap-4">
                        <!-- Left: Drag & Drop Zone -->
                        <div class="flex flex-col">
                            <div class="drag-drop-area flex-1 rounded-lg border-2 border-dashed border-gray-300 p-4 mb-4 flex flex-col items-center justify-center transition duration-300"
                                id="dragDropArea">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <h3 class="text-lg font-medium text-gray-700 mb-1">Drag & Drop Files</h3>
                                <p class="text-sm text-gray-500 text-center mb-3">or click to browse</p>
                                <input type="file" id="fileInput" multiple class="hidden">
                                <button onclick="document.getElementById('fileInput').click()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200">
                                    <i class="fas fa-folder-open mr-2"></i>Browse Files
                                </button>
                                <p class="text-xs text-gray-400 mt-3">Supports all file types</p>
                            </div>

                            <!-- Dropped Files List -->
                            <div class="flex-1 overflow-y-auto">
                                <h4 class="font-medium text-gray-700 mb-2">Dropped Files</h4>
                                <div id="droppedFilesList" class="space-y-2">
                                    <div class="text-center text-gray-500 py-4 text-sm">
                                        <i class="fas fa-file mb-1"></i>
                                        <p>No files added yet</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Paste Zone -->
                        <div class="flex flex-col">
                            <div class="flex-1 rounded-lg border border-gray-300 p-4 mb-4">
                                <h3 class="text-lg font-medium text-gray-700 mb-2">Paste Area</h3>
                                <p class="text-sm text-gray-600 mb-3">Paste any content from your clipboard (Ctrl+V)</p>
                                <textarea id="pasteArea"
                                    class="w-full h-32 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent mb-3"
                                    placeholder="Paste your content here..."></textarea>
                                <button id="clearPasteBtn" class="px-3 py-1.5 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-200">
                                    <i class="fas fa-trash-alt mr-1"></i>Clear
                                </button>
                            </div>

                            <!-- Pasted Content List -->
                            <div class="flex-1 overflow-y-auto">
                                <h4 class="font-medium text-gray-700 mb-2">Pasted Items</h4>
                                <div id="pastedItemsList" class="space-y-2">
                                    <div class="text-center text-gray-500 py-4 text-sm">
                                        <i class="fas fa-clipboard mb-1"></i>
                                        <p>No pasted items yet</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include 'elements/floating-menus.php'; ?>

    <script src="assets/script.js"></script>

    <script>
        // Search Type Data
        const searchTypes = {
            name: 'Name',
            phone: 'Phone',
            email: 'Email',
            company: 'Company',
            id: 'ID',
            position: 'Position',
            work_name: 'Work Name',
            vendor_status: 'Vendor Status',
            phone2: 'Phone 2'
        };

        // Client Search Function
        async function searchClients() {
            const type = document.getElementById('clientSearchType').value;
            const param = document.getElementById('clientSearchInput').value.trim();

            if (!param) {
                alert('Please enter a search term');
                return;
            }

            const resultsDiv = document.getElementById('clientResults');
            resultsDiv.innerHTML = `
                <div class="text-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                    <p class="mt-2 text-gray-600">Searching clients...</p>
                </div>
            `;

            try {
                const response = await fetch(`travhub-dashboard.test/travhub/api/2ndservice/client_list_by_search.php?peramiter=${encodeURIComponent(param)}&type_of_data=${type}`);
                const data = await response.json();

                displayClientResults(data);
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="text-center text-red-500 py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Error fetching results</p>
                    </div>
                `;
            }
        }

        // Vendor Search Function
        async function searchVendors() {
            const type = document.getElementById('vendorSearchType').value;
            const param = document.getElementById('vendorSearchInput').value.trim();

            if (!param) {
                alert('Please enter a search term');
                return;
            }

            const resultsDiv = document.getElementById('vendorResults');
            resultsDiv.innerHTML = `
                <div class="text-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                    <p class="mt-2 text-gray-600">Searching vendors...</p>
                </div>
            `;

            try {
                const response = await fetch(`travhub-dashboard.test/travhub/api/2ndservice/vendor_list_by_search.php?peramiter=${encodeURIComponent(param)}&type_of_data=${type}`);
                const data = await response.json();

                displayVendorResults(data);
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="text-center text-red-500 py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Error fetching results</p>
                    </div>
                `;
            }
        }

        // Display Client Results
        function displayClientResults(data) {
            const resultsDiv = document.getElementById('clientResults');

            if (!data || data.length === 0) {
                resultsDiv.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-search fa-2x mb-2"></i>
                        <p>No clients found</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="space-y-2">';

            data.forEach((client, index) => {
                html += `
                    <div class="search-result-item bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition duration-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium text-gray-800">${client.name || 'N/A'}</h4>
                                <div class="text-sm text-gray-600 mt-1 space-y-1">
                                    ${client.email ? `<div><i class="fas fa-envelope mr-1"></i> ${client.email}</div>` : ''}
                                    ${client.phone ? `<div><i class="fas fa-phone mr-1"></i> ${client.phone}</div>` : ''}
                                    ${client.company ? `<div><i class="fas fa-building mr-1"></i> ${client.company}</div>` : ''}
                                    ${client.position ? `<div><i class="fas fa-briefcase mr-1"></i> ${client.position}</div>` : ''}
                                </div>
                            </div>
                            <span class="bg-primary-100 text-primary-800 text-xs px-2 py-1 rounded">Client</span>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            resultsDiv.innerHTML = html;
        }

        // Display Vendor Results
        function displayVendorResults(data) {
            const resultsDiv = document.getElementById('vendorResults');

            if (!data || data.length === 0) {
                resultsDiv.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-search fa-2x mb-2"></i>
                        <p>No vendors found</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="space-y-2">';

            data.forEach((vendor, index) => {
                html += `
                    <div class="search-result-item bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition duration-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium text-gray-800">${vendor.name || 'N/A'}</h4>
                                <div class="text-sm text-gray-600 mt-1 space-y-1">
                                    ${vendor.email ? `<div><i class="fas fa-envelope mr-1"></i> ${vendor.email}</div>` : ''}
                                    ${vendor.phone ? `<div><i class="fas fa-phone mr-1"></i> ${vendor.phone}</div>` : ''}
                                    ${vendor.company ? `<div><i class="fas fa-building mr-1"></i> ${vendor.company}</div>` : ''}
                                    ${vendor.work_name ? `<div><i class="fas fa-tools mr-1"></i> ${vendor.work_name}</div>` : ''}
                                    ${vendor.vendor_status ? `<div><i class="fas fa-check-circle mr-1"></i> ${vendor.vendor_status}</div>` : ''}
                                </div>
                            </div>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Vendor</span>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            resultsDiv.innerHTML = html;
        }

        // File Management Functions
        let droppedFiles = [];
        let pastedItems = [];

        // Initialize Drag & Drop
        function initDragDrop() {
            const dragDropArea = document.getElementById('dragDropArea');
            const fileInput = document.getElementById('fileInput');

            // Drag over event
            dragDropArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                dragDropArea.classList.add('dragover');
            });

            // Drag leave event
            dragDropArea.addEventListener('dragleave', () => {
                dragDropArea.classList.remove('dragover');
            });

            // Drop event
            dragDropArea.addEventListener('drop', (e) => {
                e.preventDefault();
                dragDropArea.classList.remove('dragover');

                const files = e.dataTransfer.files;
                handleFiles(files);
            });

            // File input change event
            fileInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });
        }

        // Handle dropped/browsed files
        function handleFiles(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                droppedFiles.push(file);
                addFileToList(file);
            }
        }

        // Add file to dropped files list
        function addFileToList(file) {
            const filesList = document.getElementById('droppedFilesList');

            // Clear placeholder if exists
            if (filesList.children.length === 1 && filesList.children[0].classList.contains('text-center')) {
                filesList.innerHTML = '';
            }

            const fileItem = document.createElement('div');
            fileItem.className = 'file-item draggable-item bg-white border border-gray-200 rounded-lg p-3';
            fileItem.draggable = true;
            fileItem.dataset.fileName = file.name;

            const fileIcon = getFileIcon(file.type);

            fileItem.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="${fileIcon} text-xl mr-3 text-gray-500"></i>
                        <div>
                            <div class="font-medium text-gray-800 truncate max-w-xs">${file.name}</div>
                            <div class="text-xs text-gray-500">${formatFileSize(file.size)}</div>
                        </div>
                    </div>
                    <button onclick="removeFile('${file.name}')" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            // Add drag events
            fileItem.addEventListener('dragstart', handleDragStart);
            fileItem.addEventListener('dragend', handleDragEnd);

            filesList.appendChild(fileItem);
        }

        // Get file icon based on type
        function getFileIcon(fileType) {
            if (fileType.startsWith('image/')) return 'fas fa-file-image';
            if (fileType.startsWith('video/')) return 'fas fa-file-video';
            if (fileType.startsWith('audio/')) return 'fas fa-file-audio';
            if (fileType.includes('pdf')) return 'fas fa-file-pdf';
            if (fileType.includes('word')) return 'fas fa-file-word';
            if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'fas fa-file-excel';
            if (fileType.includes('zip') || fileType.includes('compressed')) return 'fas fa-file-archive';
            return 'fas fa-file';
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Remove file from list
        function removeFile(fileName) {
            droppedFiles = droppedFiles.filter(file => file.name !== fileName);

            const filesList = document.getElementById('droppedFilesList');
            const fileItems = filesList.getElementsByClassName('file-item');

            for (let i = 0; i < fileItems.length; i++) {
                if (fileItems[i].dataset.fileName === fileName) {
                    fileItems[i].remove();
                    break;
                }
            }

            // Show placeholder if no files
            if (droppedFiles.length === 0) {
                filesList.innerHTML = `
                    <div class="text-center text-gray-500 py-4 text-sm">
                        <i class="fas fa-file mb-1"></i>
                        <p>No files added yet</p>
                    </div>
                `;
            }
        }

        // Drag & Drop events for items
        function handleDragStart(e) {
            e.target.classList.add('dragging');
            e.dataTransfer.setData('text/plain', e.target.dataset.fileName);
        }

        function handleDragEnd(e) {
            e.target.classList.remove('dragging');
        }

        // Initialize paste functionality
        function initPasteArea() {
            const pasteArea = document.getElementById('pasteArea');
            const clearBtn = document.getElementById('clearPasteBtn');

            // Handle paste event
            pasteArea.addEventListener('paste', (e) => {
                setTimeout(() => {
                    const content = pasteArea.value.trim();
                    if (content) {
                        addPastedItem(content);
                        pasteArea.value = '';
                    }
                }, 100);
            });

            // Clear button
            clearBtn.addEventListener('click', () => {
                pasteArea.value = '';
            });
        }

        // Add pasted item to list
        function addPastedItem(content) {
            const pastedList = document.getElementById('pastedItemsList');

            // Clear placeholder if exists
            if (pastedList.children.length === 1 && pastedList.children[0].classList.contains('text-center')) {
                pastedList.innerHTML = '';
            }

            const itemId = 'item_' + Date.now();
            pastedItems.push({
                id: itemId,
                content: content
            });

            const item = document.createElement('div');
            item.className = 'file-item draggable-item bg-white border border-gray-200 rounded-lg p-3';
            item.draggable = true;
            item.id = itemId;

            // Truncate content for display
            const displayContent = content.length > 100 ? content.substring(0, 100) + '...' : content;

            item.innerHTML = `
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-1">
                            <i class="fas fa-clipboard text-gray-500 mr-2"></i>
                            <span class="text-xs text-gray-500">Pasted ${new Date().toLocaleTimeString()}</span>
                        </div>
                        <div class="text-sm text-gray-800 bg-gray-50 p-2 rounded border border-gray-100">
                            ${displayContent.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                    <button onclick="removePastedItem('${itemId}')" class="ml-2 text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            // Add drag events
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);

            pastedList.appendChild(item);
        }

        // Remove pasted item
        function removePastedItem(itemId) {
            pastedItems = pastedItems.filter(item => item.id !== itemId);
            document.getElementById(itemId)?.remove();

            const pastedList = document.getElementById('pastedItemsList');
            if (pastedItems.length === 0) {
                pastedList.innerHTML = `
                    <div class="text-center text-gray-500 py-4 text-sm">
                        <i class="fas fa-clipboard mb-1"></i>
                        <p>No pasted items yet</p>
                    </div>
                `;
            }
        }

        // Event Listeners
        document.getElementById('clientSearchBtn').addEventListener('click', searchClients);
        document.getElementById('vendorSearchBtn').addEventListener('click', searchVendors);

        // Allow Enter key to trigger search
        document.getElementById('clientSearchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') searchClients();
        });

        document.getElementById('vendorSearchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') searchVendors();
        });

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            initDragDrop();
            initPasteArea();

            // Sample initial data for demonstration
            setTimeout(() => {
                if (document.getElementById('droppedFilesList').children.length === 1) {
                    console.log('Drag & Drop initialized');
                }
            }, 100);
        });
    </script>
</body>

</html>