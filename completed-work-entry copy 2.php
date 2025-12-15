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

                <!-- Full Column: Drag & Drop and Paste Area -->
                <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col" style="min-height: 400px;">
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
                                id="dragDropArea" style="min-height: 180px;">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <h3 class="text-lg font-medium text-gray-700 mb-1">Drag & Drop Files</h3>
                                <p class="text-sm text-gray-500 text-center mb-3">or click to browse</p>
                                <input type="file" id="fileInput" multiple class="hidden">
                                <button onclick="document.getElementById('fileInput').click()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200">
                                    <i class="fas fa-folder-open mr-2"></i>Browse Files
                                </button>
                                <p class="text-xs text-gray-400 mt-3">Supports all file types</p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex space-x-2 mb-4">
                                <button onclick="openAllPreviews()" class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 text-sm">
                                    <i class="fas fa-external-link-alt mr-2"></i>Open All in New Tabs
                                </button>
                                <button onclick="downloadAllFiles()" class="flex-1 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200 text-sm">
                                    <i class="fas fa-download mr-2"></i>Download All
                                </button>
                                <button onclick="clearAllFiles()" class="flex-1 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200 text-sm">
                                    <i class="fas fa-trash-alt mr-2"></i>Clear All
                                </button>
                            </div>

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
                            <!-- Header for File Upload -->
                            <div class="mb-4">
                                <h3 class="text-lg font-medium text-gray-700 mb-2">Upload & Paste Area</h3>
                                <p class="text-sm text-gray-600 mb-3">Upload files directly or paste any content from your clipboard (Ctrl+V)</p>
                            </div>

                            <!-- FilePond Upload Zone -->
                            <div class="mb-4">
                                <input type="file" class="filepond" name="filepond" multiple data-max-file-size="10MB" data-max-files="10" id="filepond">
                            </div>

                            <!-- Separator -->
                            <div class="relative my-4">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">Or paste content below</span>
                                </div>
                            </div>

                            <!-- Paste Area -->
                            <div class="rounded-lg border border-gray-300 p-4 mb-4">
                                <h4 class="font-medium text-gray-700 mb-2">Paste Text Content</h4>
                                <textarea id="pasteArea" placeholder="Paste your text content here..." class="w-full h-32 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent mb-3"></textarea>
                                <div class="flex space-x-2">
                                    <button id="clearPasteBtn" class="px-3 py-1.5 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-200">
                                        <i class="fas fa-trash-alt mr-1"></i>Clear
                                    </button>
                                    <button id="pasteBtn" class="px-3 py-1.5 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200">
                                        <i class="fas fa-paste mr-1"></i>Paste Now
                                    </button>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex space-x-2 mb-4">
                                <button onclick="openAllPastedItems()" class="flex-1 px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition duration-200 text-sm">
                                    <i class="fas fa-external-link-alt mr-2"></i>View All Items
                                </button>
                                <button onclick="clearAllPastedItems()" class="flex-1 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200 text-sm">
                                    <i class="fas fa-trash-alt mr-2"></i>Clear All
                                </button>
                            </div>

                            <!-- Pasted Items List -->
                            <div class="flex-1 overflow-y-auto">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-medium text-gray-700">Pasted Items</h4>
                                    <span id="pasteCount" class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded">0 items</span>
                                </div>
                                <div id="pastedItemsList" class="space-y-2 custom-scrollbar">
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

        // Open all pasted items in new tab
        function openAllPastedItems() {
            if (pastedItems.length === 0) {
                alert('No pasted items to view!');
                return;
            }

            const newTab = window.open('', '_blank');
            let content = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>All Pasted Items (${pastedItems.length})</title>
                    <style>
                        body { 
                            margin: 0; 
                            padding: 20px; 
                            background: #f0f0f0; 
                            font-family: Arial, sans-serif;
                        }
                        .container {
                            max-width: 800px;
                            margin: 0 auto;
                            background: white;
                            padding: 30px;
                            border-radius: 10px;
                            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                        }
                        .item {
                            border: 1px solid #ddd;
                            border-radius: 5px;
                            padding: 15px;
                            margin-bottom: 15px;
                            background: #f9f9f9;
                        }
                        .item-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 10px;
                            padding-bottom: 10px;
                            border-bottom: 1px solid #eee;
                        }
                        .timestamp {
                            color: #666;
                            font-size: 12px;
                        }
                        .content {
                            white-space: pre-wrap;
                            word-wrap: break-word;
                            font-family: monospace;
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>All Pasted Items (${pastedItems.length})</h1>
            `;

            pastedItems.forEach((item, index) => {
                content += `
                    <div class="item">
                        <div class="item-header">
                            <span><strong>Item ${index + 1}</strong></span>
                            <span class="timestamp">${item.timestamp}</span>
                        </div>
                        <div class="content">${item.content}</div>
                    </div>
                `;
            });

            content += `
                    </div>
                </body>
                </html>
            `;

            newTab.document.write(content);
            newTab.document.close();
        }

        // Clear all pasted items
        function clearAllPastedItems() {
            if (pastedItems.length === 0) {
                alert('No pasted items to clear!');
                return;
            }

            if (confirm(`Are you sure you want to remove all ${pastedItems.length} pasted items?`)) {
                pastedItems = [];
                document.getElementById('pastedItemsList').innerHTML = `
                    <div class="text-center text-gray-500 py-4 text-sm">
                        <i class="fas fa-clipboard mb-1"></i>
                        <p>No pasted items yet</p>
                    </div>
                `;
                updatePasteCount();
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

        // Update paste count
        function updatePasteCount() {
            const countElement = document.getElementById('pasteCount');
            countElement.textContent = `${pastedItems.length} item${pastedItems.length !== 1 ? 's' : ''}`;
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

        // Initialize paste functionality
        function initPasteArea() {
            const pasteArea = document.getElementById('pasteArea');
            const clearBtn = document.getElementById('clearPasteBtn');
            const pasteBtn = document.getElementById('pasteBtn');

            pasteBtn.addEventListener('click', () => {
                const content = pasteArea.value.trim();
                if (content) {
                    addPastedItem(content);
                    pasteArea.value = '';
                    updatePasteCount();
                }
            });

            pasteArea.addEventListener('paste', (e) => {
                setTimeout(() => {
                    const content = pasteArea.value.trim();
                    if (content && content.length > 10) {
                        addPastedItem(content);
                        pasteArea.value = '';
                        updatePasteCount();
                    }
                }, 100);
            });

            clearBtn.addEventListener('click', () => {
                pasteArea.value = '';
                pasteArea.focus();
            });

            pasteArea.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'Enter') {
                    const content = pasteArea.value.trim();
                    if (content) {
                        addPastedItem(content);
                        pasteArea.value = '';
                        updatePasteCount();
                    }
                }
            });
        }

        // Add pasted item to list
        function addPastedItem(content) {
            const pastedList = document.getElementById('pastedItemsList');

            if (pastedList.children.length === 1 && pastedList.children[0].classList.contains('text-center')) {
                pastedList.innerHTML = '';
            }

            const itemId = 'item_' + Date.now();
            const timestamp = new Date().toLocaleTimeString();
            pastedItems.push({
                id: itemId,
                content: content,
                timestamp: timestamp
            });

            const item = document.createElement('div');
            item.className = 'file-item bg-white border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition duration-200';
            item.id = itemId;

            const displayContent = content.length > 100 ? content.substring(0, 100) + '...' : content;

            item.innerHTML = `
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-clipboard mr-1"></i>${timestamp}
                            </span>
                            <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">${content.length} chars</span>
                        </div>
                        <div class="text-sm text-gray-800 bg-gray-50 p-2 rounded border border-gray-100 overflow-hidden">
                            ${displayContent.replace(/\n/g, '<br>')}
                        </div>
                        ${content.length > 100 ? 
                            `<div class="text-xs text-primary-600 mt-1 cursor-pointer hover:underline" onclick="viewPastedItem('${itemId}')">
                                <i class="fas fa-external-link-alt mr-1"></i>View in New Tab
                            </div>` : ''}
                    </div>
                    <button onclick="removePastedItem('${itemId}')" class="ml-2 text-red-500 hover:text-red-700" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            pastedList.prepend(item);
        }

        // View pasted item in new tab
        function viewPastedItem(itemId) {
            const item = pastedItems.find(item => item.id === itemId);
            if (!item) return;

            const newTab = window.open('', '_blank');
            newTab.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Pasted Item</title>
                    <style>
                        body { 
                            margin: 0; 
                            padding: 20px; 
                            background: #f0f0f0; 
                            font-family: Arial, sans-serif;
                        }
                        .container {
                            max-width: 800px;
                            margin: 0 auto;
                            background: white;
                            padding: 30px;
                            border-radius: 10px;
                            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                        }
                        .header {
                            background: #f8f9fa;
                            padding: 15px;
                            border-radius: 5px;
                            margin-bottom: 20px;
                        }
                        .content {
                            white-space: pre-wrap;
                            word-wrap: break-word;
                            font-family: monospace;
                            font-size: 14px;
                            line-height: 1.6;
                            padding: 20px;
                            background: #f9f9f9;
                            border-radius: 5px;
                            border: 1px solid #eee;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1 style="margin: 0 0 10px 0;">Pasted Content</h1>
                            <p style="margin: 0; color: #666;">Pasted at: ${item.timestamp}</p>
                            <p style="margin: 5px 0 0 0; color: #666;">Length: ${item.content.length} characters</p>
                        </div>
                        <div class="content">${item.content}</div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button onclick="window.print()" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-print"></i> Print Content
                            </button>
                        </div>
                    </div>
                </body>
                </html>
            `);
            newTab.document.close();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Register plugins
            FilePond.registerPlugin(
                FilePondPluginFileValidateType,
                FilePondPluginFileValidateSize,
                FilePondPluginImagePreview
            );

            // Create the FilePond instance
            const pond = FilePond.create(document.querySelector('#filepond'), {
                allowMultiple: true,
                maxFiles: 10,
                maxFileSize: '10MB',
                // Configure your server endpoint for file processing
                server: {
                    process: './upload.php', // You will need to create this endpoint
                    revert: './delete.php', // Optional: for handling file removal
                },
                // Accepted file types
                acceptedFileTypes: [
                    'image/*',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain',
                    'application/zip'
                ],
                labelIdle: 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>',
                labelFileProcessingComplete: 'Upload complete',
                labelTapToCancel: 'Tap to cancel',
                labelTapToUndo: 'Tap to undo',
            });

            // Initialize your custom paste area
            initPasteArea();
        });

        // Remove pasted item
        function removePastedItem(itemId) {
            if (!confirm('Are you sure you want to remove this item?')) return;

            pastedItems = pastedItems.filter(item => item.id !== itemId);
            document.getElementById(itemId)?.remove();
            updatePasteCount();

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
            initPasteArea();
        });
    </script>
</body>

</html>