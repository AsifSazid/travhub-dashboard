<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Work Entry</title>
    <link rel="icon" type="image/png" href="./assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
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

        /* Scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Preview Modal */
        .preview-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease;
        }

        .preview-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
    <!-- Add to the <head> section for FilePond -->
    <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet">
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">
    <script src="https://unpkg.com/filepond@^4/dist/filepond.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include 'elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include 'elements/aside.php'; ?>

    <!-- Preview Modal -->
    <div id="previewModal" class="preview-modal">
        <div class="preview-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800" id="previewTitle">File Preview</h3>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalPreviewContent" class="p-4">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <div class="grid grid-cols-6 gap-4">

                <!-- Full Column: Drag & Drop and Paste Area -->
                <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col" style="min-height: 200px;">
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
                            <!-- Drag & Drop Zone -->
                            <div class="drag-drop-area rounded-lg border-2 border-dashed border-gray-300 p-6 mb-4 flex flex-col items-center justify-center transition duration-300 hover:bg-gray-50"
                                id="dragDropArea" style="min-height: 80px;">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <input type="file" id="fileInput" multiple class="hidden">
                                <button onclick="document.getElementById('fileInput').click()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200">
                                    <i class="fas fa-folder-open mr-2"></i>Browse Files
                                </button>
                            </div>

                            <!-- Action Buttons -->
                            <!-- <div class="flex space-x-2 mb-4">
                                <button onclick="openAllPreviews()" class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 text-sm">
                                    <i class="fas fa-external-link-alt mr-2"></i>Open All in New Tabs
                                </button>
                                <button onclick="downloadAllFiles()" class="flex-1 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200 text-sm">
                                    <i class="fas fa-download mr-2"></i>Download All
                                </button>
                                <button onclick="clearAllFiles()" class="flex-1 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200 text-sm">
                                    <i class="fas fa-trash-alt mr-2"></i>Clear All
                                </button>
                            </div> -->

                            <!-- Dropped Files List -->
                            <div class="file-list-container flex-1 overflow-y-auto">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-medium text-gray-700">Dropped Files</h4>
                                    <span id="fileCount" class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded">0 files</span>
                                </div>
                                <div id="droppedFilesList" class="space-y-2 custom-scrollbar">
                                    <div class="text-center text-gray-500 py-4 text-sm">
                                        <i class="fas fa-file mb-1"></i>
                                        <p>No files added yet</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: File Upload & Paste Zone -->
                        <div class="flex flex-col">
                            <button id="uploadPasteBtn" style="margin-top:1rem;padding:0.5rem 1rem;background:#6b21a8;color:white;border:none;border-radius:0.25rem;cursor:pointer;">
                                <i class="fas fa-upload"></i> Save Pasted Content
                            </button>

                            <textarea id="pasteArea" placeholder="Paste content here" style="width:100%;height:100px;margin-top:0.5rem;padding:0.5rem;border:1px solid #ccc;border-radius:0.25rem;"></textarea>

                            <h4 style="margin-top:1rem;">Pasted Items</h4>
                            <div id="pastedItemsList"></div>
                        </div>
                    </div>
                </div>

                <!-- Left Column: Client Search -->
                <div class="col-span-3 bg-white rounded-lg shadow p-4 flex flex-col">
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
                    <div id="clientResults" class="flex-1 overflow-y-auto border border-gray-200 rounded-lg p-2 bg-gray-50 custom-scrollbar min-h-[200px]">
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p>Search results will appear here</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Vendor Search -->
                <div class="col-span-3 bg-white rounded-lg shadow p-4 flex flex-col">
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
                    <div id="vendorResults" class="flex-1 overflow-y-auto border border-gray-200 rounded-lg p-2 bg-gray-50 custom-scrollbar min-h-[200px]">
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p>Search results will appear here</p>
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
                if (files.length > 0) {
                    handleFiles(files);
                }
            });

            // File input change event
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleFiles(e.target.files);
                    e.target.value = '';
                }
            });

            // Click on drag drop area to trigger file input
            dragDropArea.addEventListener('click', (e) => {
                if (e.target.tagName !== 'BUTTON' && !e.target.closest('button')) {
                    fileInput.click();
                }
            });
        }

        // Handle dropped/browsed files
        function handleFiles(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const existingIndex = droppedFiles.findIndex(f => f.name === file.name && f.size === file.size);

                if (existingIndex === -1) {
                    droppedFiles.push(file);
                    addFileToList(file);
                } else {
                    alert(`File "${file.name}" already exists!`);
                }
            }
            updateFileCount();
        }

        // Add file to dropped files list
        function addFileToList(file) {
            const filesList = document.getElementById('droppedFilesList');

            // Clear placeholder if exists
            if (filesList.children.length === 1 && filesList.children[0].classList.contains('text-center')) {
                filesList.innerHTML = '';
            }

            const fileItem = document.createElement('div');
            fileItem.className = 'file-item bg-white border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition duration-200 cursor-pointer';
            fileItem.dataset.fileName = file.name;
            fileItem.dataset.fileSize = file.size;
            fileItem.dataset.fileType = file.type;

            const fileIcon = getFileIcon(file.type);
            const isImage = file.type.startsWith('image/');

            fileItem.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center flex-1">
                        <i class="${fileIcon} text-xl mr-3 ${isImage ? 'text-blue-500' : 'text-gray-500'}"></i>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-800 truncate" title="${file.name}">${file.name}</div>
                            <div class="text-xs text-gray-500">${formatFileSize(file.size)} • ${file.type || 'Unknown type'}</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="previewInNewTab('${file.name}')" class="text-blue-500 hover:text-blue-700" title="Preview in New Tab">
                            <i class="fas fa-external-link-alt"></i>
                        </button>
                        <button onclick="downloadFile('${file.name}')" class="text-green-500 hover:text-green-700" title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                        <button onclick="removeFile('${file.name}')" class="text-red-500 hover:text-red-700" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;

            // Click to open in new tab
            fileItem.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    previewInNewTab(file.name);
                }
            });

            filesList.appendChild(fileItem);
        }

        // Preview in new tab
        function previewInNewTab(fileName) {
            const file = droppedFiles.find(f => f.name === fileName);
            if (!file) return;

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const newTab = window.open('', '_blank');
                    newTab.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Preview: ${file.name}</title>
                            <style>
                                body { 
                                    margin: 0; 
                                    padding: 20px; 
                                    background: #f0f0f0; 
                                    display: flex; 
                                    justify-content: center; 
                                    align-items: center; 
                                    min-height: 100vh;
                                }
                                .preview-container {
                                    max-width: 90%;
                                    max-height: 90vh;
                                    background: white;
                                    padding: 20px;
                                    border-radius: 10px;
                                    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                                    text-align: center;
                                }
                                img { 
                                    max-width: 100%; 
                                    max-height: 70vh; 
                                    border-radius: 5px;
                                }
                                .file-info {
                                    margin-top: 15px;
                                    padding: 10px;
                                    background: #f8f9fa;
                                    border-radius: 5px;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="preview-container">
                                <img src="${e.target.result}" alt="${file.name}">
                                <div class="file-info">
                                    <h3>${file.name}</h3>
                                    <p>Size: ${formatFileSize(file.size)} | Type: ${file.type}</p>
                                    <button onclick="window.print()" style="margin-top: 10px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            </div>
                        </body>
                        </html>
                    `);
                    newTab.document.close();
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('text/') || file.type.includes('pdf') ||
                file.type.includes('word') || file.type.includes('excel')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const newTab = window.open('', '_blank');
                    if (file.type.includes('pdf')) {
                        // For PDF, create an iframe
                        const pdfUrl = URL.createObjectURL(file);
                        newTab.document.write(`
                            <!DOCTYPE html>
                            <html>
                            <head>
                                <title>PDF Preview: ${file.name}</title>
                                <style>
                                    body { margin: 0; padding: 20px; background: #f0f0f0; }
                                    .pdf-container { 
                                        width: 100%; 
                                        height: 90vh; 
                                        background: white; 
                                        border-radius: 10px; 
                                        overflow: hidden;
                                        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                                    }
                                    iframe { width: 100%; height: 100%; border: none; }
                                </style>
                            </head>
                            <body>
                                <div class="pdf-container">
                                    <iframe src="${pdfUrl}"></iframe>
                                </div>
                            </body>
                            </html>
                        `);
                    } else {
                        // For text files
                        newTab.document.write(`
                            <!DOCTYPE html>
                            <html>
                            <head>
                                <title>Preview: ${file.name}</title>
                                <style>
                                    body { 
                                        margin: 0; 
                                        padding: 20px; 
                                        background: #f0f0f0; 
                                        font-family: monospace;
                                    }
                                    .preview-container {
                                        max-width: 90%;
                                        background: white;
                                        padding: 20px;
                                        border-radius: 10px;
                                        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                                        overflow-x: auto;
                                    }
                                    pre { 
                                        white-space: pre-wrap; 
                                        word-wrap: break-word; 
                                        margin: 0;
                                        font-size: 14px;
                                    }
                                    .file-header {
                                        background: #f8f9fa;
                                        padding: 10px;
                                        border-radius: 5px;
                                        margin-bottom: 15px;
                                        display: flex;
                                        justify-content: space-between;
                                        align-items: center;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="preview-container">
                                    <div class="file-header">
                                        <div>
                                            <h3 style="margin: 0;">${file.name}</h3>
                                            <p style="margin: 5px 0 0 0; color: #666;">Size: ${formatFileSize(file.size)}</p>
                                        </div>
                                        <button onclick="window.print()" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                    </div>
                                    <pre>${e.target.result}</pre>
                                </div>
                            </body>
                            </html>
                        `);
                    }
                    newTab.document.close();
                };
                if (file.type.startsWith('text/')) {
                    reader.readAsText(file);
                } else {
                    reader.readAsDataURL(file);
                }
            } else {
                // For other file types
                const newTab = window.open('', '_blank');
                newTab.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>File Info: ${file.name}</title>
                        <style>
                            body { 
                                margin: 0; 
                                padding: 20px; 
                                background: #f0f0f0; 
                                display: flex; 
                                justify-content: center; 
                                align-items: center; 
                                min-height: 100vh;
                            }
                            .info-container {
                                background: white;
                                padding: 30px;
                                border-radius: 10px;
                                box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                                text-align: center;
                                max-width: 500px;
                            }
                            .file-icon {
                                font-size: 48px;
                                color: #666;
                                margin-bottom: 20px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="info-container">
                            <div class="file-icon">
                                <i class="${getFileIcon(file.type)}"></i>
                            </div>
                            <h2>${file.name}</h2>
                            <p><strong>Type:</strong> ${file.type || 'Unknown'}</p>
                            <p><strong>Size:</strong> ${formatFileSize(file.size)}</p>
                            <p><strong>Last Modified:</strong> ${new Date(file.lastModified).toLocaleString()}</p>
                            <p style="margin-top: 20px; color: #666;">
                                This file type cannot be previewed in the browser.
                                Please download the file to view it.
                            </p>
                            <button onclick="window.location.href='${URL.createObjectURL(file)}';" 
                                style="margin-top: 20px; padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-download"></i> Download File
                            </button>
                        </div>
                    </body>
                    </html>
                `);
                newTab.document.close();
            }
        }

        // Open all previews in new tabs
        function openAllPreviews() {
            if (droppedFiles.length === 0) {
                alert('No files to preview!');
                return;
            }

            // Open each file in a new tab
            droppedFiles.forEach((file, index) => {
                setTimeout(() => {
                    previewInNewTab(file.name);
                }, index * 300); // Stagger the openings
            });
        }

        // Download all files as zip (simplified version)
        function downloadAllFiles() {
            if (droppedFiles.length === 0) {
                alert('No files to download!');
                return;
            }

            // Download each file individually (for simplicity)
            droppedFiles.forEach((file, index) => {
                setTimeout(() => {
                    downloadFile(file.name);
                }, index * 500);
            });

            alert(`${droppedFiles.length} files will be downloaded one by one.`);
        }

        // Clear all files
        function clearAllFiles() {
            if (droppedFiles.length === 0) {
                alert('No files to clear!');
                return;
            }

            if (confirm(`Are you sure you want to remove all ${droppedFiles.length} files?`)) {
                droppedFiles = [];
                document.getElementById('droppedFilesList').innerHTML = `
                    <div class="text-center text-gray-500 py-4 text-sm">
                        <i class="fas fa-file mb-1"></i>
                        <p>No files added yet</p>
                    </div>
                `;
                updateFileCount();
            }
        }

        // Download file
        function downloadFile(fileName) {
            const file = droppedFiles.find(f => f.name === fileName);
            if (!file) return;

            const url = URL.createObjectURL(file);
            const a = document.createElement('a');
            a.href = url;
            a.download = file.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
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
            if (fileType.startsWith('text/')) return 'fas fa-file-alt';
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

        // Update file count
        function updateFileCount() {
            const countElement = document.getElementById('fileCount');
            countElement.textContent = `${droppedFiles.length} file${droppedFiles.length !== 1 ? 's' : ''}`;
        }

        // Remove file from list
        function removeFile(fileName) {
            if (!confirm('Are you sure you want to remove this file?')) return;

            droppedFiles = droppedFiles.filter(file => file.name !== fileName);
            updateFileCount();

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

        // 
        // Register FilePond plugins
        FilePond.registerPlugin(
            FilePondPluginFileValidateType,
            FilePondPluginFileValidateSize,
            FilePondPluginImagePreview
        );

        // Create FilePond instance
        const pond = FilePond.create(document.querySelector('#filepond'), {
            server: {
                process: {
                    url: '',
                    method: 'POST',
                    withCredentials: false,
                    headers: {},
                    timeout: 7000,
                    onload: (response) => {
                        const res = JSON.parse(response);
                        if (res.status === 'success') alert('Files uploaded successfully!');
                        else alert(res.message);
                    }
                }
            },
            acceptedFileTypes: ['image/*', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain', 'application/zip'],
            allowMultiple: true,
            instantUpload: true
        });

        // Handle pasted content
        const pasteArea = document.getElementById('pasteArea');
        const uploadPasteBtn = document.getElementById('uploadPasteBtn');
        const pastedItemsList = document.getElementById('pastedItemsList');

        uploadPasteBtn.addEventListener('click', async () => {
            const content = pasteArea.value.trim();
            if (!content) {
                alert('Paste something first');
                return;
            }
            const fd = new FormData();
            fd.append('pastedContent', content);

            const res = await fetch('', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if (data.status === 'success') {
                const div = document.createElement('div');
                div.className = 'item';
                div.textContent = content.length > 100 ? content.substring(0, 100) + '...' : content;
                pastedItemsList.appendChild(div);
                pasteArea.value = '';
                alert('Pasted content saved!');
            } else {
                alert(data.message);
            }
        });

        // Optional: handle Ctrl+V paste directly for preview (without saving)
        pasteArea.addEventListener('paste', (e) => {
            const items = e.clipboardData.items;
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                if (item.kind === 'file') {
                    const blob = item.getAsFile();
                    const url = URL.createObjectURL(blob);
                    const div = document.createElement('div');
                    div.className = 'item';
                    div.innerHTML = `<strong>${blob.name || 'pasted file'}</strong> (<a href="${url}" target="_blank">View</a>)`;
                    pastedItemsList.appendChild(div);
                } else if (item.kind === 'string') {
                    item.getAsString(text => {
                        if (text.trim() === '') return;
                        const div = document.createElement('div');
                        div.className = 'item';
                        div.textContent = text.length > 100 ? text.substring(0, 100) + '...' : text;
                        pastedItemsList.appendChild(div);
                    });
                }
            }
        });
        // 

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
                const response = await fetch(`http://103.104.219.3:898/travhub/api/2ndservice/client_list_by_search.php?peramiter=${encodeURIComponent(param)}&type_of_data=${type}`, {
                    method: 'GET',
                    mode: 'cors', // CORS মোড
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    // credentials: 'include' // যদি authentication লাগে
                });

                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('Client Data:', data);

                displayClientResults(data);
            } catch (error) {
                console.error('Error:', error);
                resultsDiv.innerHTML = `
                    <div class="text-center text-red-500 py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Error fetching results: ${error.message}</p>
                        <p class="text-sm mt-2">CORS issue detected. Please try:</p>
                        <ol class="text-sm text-left mt-2">
                            <li>1. Check API server CORS configuration</li>
                            <li>2. Use proxy server</li>
                            <li>3. Contact API provider</li>
                        </ol>
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
                const response = await fetch(`http://103.104.219.3:898/travhub/api/2ndservice/vendor_list_by_search.php?peramiter=${encodeURIComponent(param)}&type_of_data=${type}`);
                const data = await response.json();

                displayVendorResults(data);
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="text-center text-red-500 py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Error fetching results: ${error.message}</p>
                        <p class="text-sm mt-2">CORS issue detected. Please try:</p>
                        <ol class="text-sm text-left mt-2">
                            <li>1. Check API server CORS configuration</li>
                            <li>2. Use proxy server</li>
                            <li>3. Contact API provider</li>
                        </ol>
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

        // Event Listeners
        document.getElementById('clientSearchBtn').addEventListener('click', searchClients);
        document.getElementById('vendorSearchBtn').addEventListener('click', searchVendors);

        document.getElementById('clientSearchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') searchClients();
        });

        document.getElementById('vendorSearchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') searchVendors();
        });

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            initDragDrop();
        });
    </script>
</body>

</html>