<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$workId = $_GET['work_id'];

$getAllTasksForWorkApi = $ip_port . "api/tasks/tasks-for-work.php?work_id=$workId";
$storeTasksApi = $ip_port . "api/tasks/store.php";
$getWorkFinEntriesApi = $ip_port . "api/financial_entries/work-fin-entries.php?work_id=$workId";
$getWorkInfo = $ip_port . "api/clients/get-client.php?work_id=$workId";


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Work Entry</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
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

        .context-menu-item {
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 6px 8px;
            border-radius: 4px;
            user-select: none;
        }

        .context-menu-item:hover {
            background-color: #374151;
            /* Tailwind gray-700 */
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
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

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
            <div class="bg-white rounded-lg shadow p-4">
                <div id="taskTab" class="tab-content">

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span">
                            <div class="flex border-b mb-6">
                                <button
                                    class="tab-btn px-4 py-2 text-sm font-medium"
                                    onclick="window.location.href='/pages/completed-work-entry.php'">
                                    <i class="fas fa-tasks mr-1"></i> General Information
                                </button>
                                <button
                                    class="tab-btn px-4 py-2 text-sm font-medium border-b-2 border-purple-600 text-purple-600"
                                    data-tab="taskTab">
                                    <i class="fas fa-tasks mr-1"></i> Task Management
                                </button>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-1">
                                Task Management
                            </h2>
                            <p class="text-sm text-gray-600 mb-4">
                                Drag & drop files or paste content from clipboard
                            </p>

                            <form id="taskForm">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Left -->
                                    <div>
                                        <div class="mb-4">
                                            <label for="taskCategory" class="block text-sm font-medium text-gray-700 my-2">Task Category</label>
                                            <select id="taskCategory" name="task_category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                                <option value="">Search For</option>
                                                <option value="1">Air Ticket Issue</option>
                                                <option value="2">Hotel Booking</option>
                                            </select>
                                        </div>

                                        <label class="block text-sm font-medium text-gray-700 my-2">Upload or Paste Your Documents</label>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div id="dragDropArea" class="rounded-lg border-2 border-dashed border-gray-300 p-6 mb-4 flex flex-col items-center justify-center hover:bg-gray-50">
                                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                                <input type="file" id="fileInput" multiple class="hidden">
                                                <button
                                                    type="button"
                                                    onclick="document.getElementById('fileInput').click()"
                                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                                    Browse Files
                                                    <!-- <i class="fas fa-folder-open mr-1"></i>  -->
                                                </button>
                                            </div>

                                            <textarea id="pasteArea"
                                                placeholder="Paste content here"
                                                class="w-full h-36 p-2 border-2 border-dashed border-gray-300 rounded"></textarea>

                                        </div>

                                    </div>

                                    <!-- Right -->
                                    <div>
                                        <label for="infoFileName" class="block text-sm font-medium text-gray-700 my-2">
                                            Information File
                                        </label>
                                        <input type="text" id="infoFileName" name="infoFileName" class="w-full px-3 py-2 mb-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                        <label for="infoArea" class="block text-sm font-medium text-gray-700 mb-2">
                                            Information
                                        </label>
                                        <textarea id="infoArea" rows="5" class="w-full p-2 border rounded-lg" placeholder="Write your information"></textarea>
                                    </div>

                                    <!-- Full -->
                                    <div class="col-span-2">
                                        <div class="mt-4">
                                            <div class="flex justify-between items-center mb-2">
                                                <h4 class="text-sm font-medium">Dropped or Pasted Files</h4>
                                                <span id="fileCount" class="text-xs bg-gray-200 px-2 py-1 rounded">0 files</span>
                                            </div>
                                        </div>
                                        <div id="droppedFilesList" class="text-sm text-gray-500">
                                            No files added yet
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="flex-1 px-4 py-2 mt-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">Submit</button>

                            </form>
                        </div>
                        <div class="col-span">
                            <?php include('te-work-folder.php') ?>
                        </div>
                    </div>

                    <hr class="my-6">

                    <div class="bg-white rounded-lg shadow p-4 flex flex-col">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                            <h2 class="text-2xl font-semibold text-gray-800">Task Lists</h2>

                            <!-- Search Area -->
                            <div id="typing-search" class="w-full md:w-auto">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input
                                        type="text"
                                        id="taskSearchInput"
                                        placeholder="Search tasks by ID, title, category, or work..."
                                        class="pl-10 pr-4 py-2.5 w-full md:w-80 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition-all duration-200">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span id="searchResultCount" class="text-xs text-gray-400 hidden">0 found</span>
                                    </div>
                                </div>

                                <!-- Search Filters -->
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <div class="flex items-center space-x-2">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="searchFilter" value="id" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">ID</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="searchFilter" value="title" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">Title</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="searchFilter" value="category" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">Category</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="searchFilter" value="work" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">Work</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="searchFilter" value="files" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">Files</span>
                                        </label>
                                    </div>

                                    <button id="clearSearch" class="ml-2 px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors">
                                        <i class="fas fa-times mr-1"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Loading Indicator -->
                        <div id="loadingIndicator" class="hidden flex items-center justify-center p-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <span class="ml-3 text-gray-600">Loading tasks...</span>
                        </div>

                        <!-- No Results Message -->
                        <div id="noResultsMessage" class="hidden text-center p-8">
                            <i class="fas fa-search text-gray-300 text-4xl mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-700 mb-1">No tasks found</h3>
                            <p class="text-gray-500">Try adjusting your search or filters</p>
                        </div>

                        <!-- Tasks Container -->
                        <div class="overflow-x-auto table-container">
                            <div id="tasksContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 p-4">
                                <!-- Task cards will be dynamically inserted here -->
                            </div>
                        </div>

                        <!-- Results Info -->
                        <div id="resultsInfo" class="mt-4 pt-4 border-t border-gray-200 hidden">
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <span id="totalTasksCount">0 tasks</span>
                                <span id="filteredTasksCount" class="font-medium"></span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-6">

                    <div class="bg-white rounded-lg shadow p-4 flex flex-col">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Financial Transactions - Work Wise</h2>

                        <div class="overflow-x-auto table-container">
                            <table id="finTable" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client/Vendor Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="finTableBody" class="bg-white divide-y divide-gray-200">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>

    <script>
        const API_URL_FOR_TASK_STORE = "<?php echo $storeTasksApi; ?>";
        const API_URL_FOR_ALL_TASKS_FOR_WORK = "<?php echo $getAllTasksForWorkApi; ?>";
        const GET_FINANCIAL_STATEMENT_API = "<?php echo $getWorkFinEntriesApi; ?>";

        // File Management Variables
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
                    e.target.value = ''; // Reset input
                }
            });

            // Click on drag drop area to trigger file input
            dragDropArea.addEventListener('click', (e) => {
                // Only trigger if clicking on the area itself, not on buttons
                if (e.target === dragDropArea || e.target.tagName === 'I') {
                    fileInput.click();
                }
            });

            // Separate handler for browse button
            const browseButton = dragDropArea.querySelector('button');
            browseButton.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event from bubbling to parent
                fileInput.click();
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
            const fileCount = droppedFiles.length;
            countElement.textContent = `${fileCount} file${fileCount !== 1 ? 's' : ''}`;

            // Update count color based on number of files
            if (fileCount === 0) {
                countElement.className = 'text-xs bg-gray-200 px-2 py-1 rounded';
            } else if (fileCount < 5) {
                countElement.className = 'text-xs bg-green-100 text-green-800 px-2 py-1 rounded';
            } else {
                countElement.className = 'text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded';
            }
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

        // Store Task Form Submission
        const form = document.getElementById('taskForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent page reload

            // Create FormData
            const formData = new FormData();

            // Get values
            const taskCategory = document.getElementById('taskCategory').value;
            const infoFileName = document.getElementById('infoFileName').value;
            const infoArea = document.getElementById('infoArea').value;
            const pasteArea = document.getElementById('pasteArea').value;
            const workId = "<?php echo $workId; ?>";

            // Validate required fields
            if (!taskCategory) {
                alert('Please select a task category');
                return;
            }

            // Append normal fields
            formData.append('task_category', taskCategory);
            formData.append('info_file_name', infoFileName);
            formData.append('information', infoArea);
            formData.append('pasted_text', pasteArea);
            formData.append('work_id', workId);

            // Append files
            if (droppedFiles.length > 0) {
                droppedFiles.forEach(file => {
                    formData.append('files[]', file);
                });
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';
            submitBtn.disabled = true;

            // Send to API
            fetch(API_URL_FOR_TASK_STORE, {
                    method: 'POST',
                    body: formData // FormData will automatically set Content-Type
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(data => {
                    console.log('API Response:', data);

                    if (data.success) {
                        alert('Task saved successfully!');
                        // Reset form
                        form.reset();
                        // Clear files
                        droppedFiles = [];
                        document.getElementById('droppedFilesList').innerHTML = `
                            <div class="text-center text-gray-500 py-4 text-sm">
                                <i class="fas fa-file mb-1"></i>
                                <p>No files added yet</p>
                            </div>
                        `;
                        updateFileCount();

                        // Optionally refresh task list
                        loadTasks();
                    } else {
                        alert(data.message || 'Something went wrong');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Server or network error. Please try again.');
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Paste functionality
        const pasteArea = document.getElementById('pasteArea');

        pasteArea.addEventListener('paste', (e) => {
            const items = e.clipboardData.items;
            let hasFiles = false;

            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                if (item.kind === 'file') {
                    hasFiles = true;
                    // Get the file from clipboard
                    const blob = item.getAsFile();

                    // Convert to File object
                    const file = new File([blob], blob.name || `pasted_file_${Date.now()}`, {
                        type: blob.type,
                        lastModified: Date.now()
                    });

                    // Add to droppedFiles array
                    const existingIndex = droppedFiles.findIndex(f => f.name === file.name && f.size === file.size);

                    if (existingIndex === -1) {
                        droppedFiles.push(file);
                        addFileToList(file);
                        updateFileCount();
                    } else {
                        alert(`File "${file.name}" already exists!`);
                    }
                }
            }

            // Prevent default behavior for files (to avoid text duplication)
            if (hasFiles) {
                e.preventDefault();
            }
        });


        // get all tasks
        // Global variables
        let allTasks = [];
        let searchTimeout;

        // Get all tasks
        const tasksContainer = document.getElementById('tasksContainer');
        const taskSearchInput = document.getElementById('taskSearchInput');
        const searchResultCount = document.getElementById('searchResultCount');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const noResultsMessage = document.getElementById('noResultsMessage');
        const resultsInfo = document.getElementById('resultsInfo');
        const totalTasksCount = document.getElementById('totalTasksCount');
        const filteredTasksCount = document.getElementById('filteredTasksCount');
        const clearSearchBtn = document.getElementById('clearSearch');

        function loadTasks() {
            loadingIndicator.classList.remove('hidden');
            tasksContainer.innerHTML = '';

            fetch(API_URL_FOR_ALL_TASKS_FOR_WORK)
                .then(res => res.json())
                .then(data => {
                    allTasks = data.tasks || [];
                    renderCards(allTasks);
                    updateResultsInfo(allTasks.length, allTasks.length);
                    loadingIndicator.classList.add('hidden');
                })
                .catch(err => {
                    console.error('Error fetching data:', err);
                    loadingIndicator.classList.add('hidden');
                });
        }

        function renderCards(tasks) {
            // Clear previous data
            tasksContainer.innerHTML = '';

            if (tasks.length === 0) {
                noResultsMessage.classList.remove('hidden');
                tasksContainer.classList.add('hidden');
                return;
            }

            noResultsMessage.classList.add('hidden');
            tasksContainer.classList.remove('hidden');

            tasks.forEach(task => {
                const card = document.createElement('a');
                card.href = `cwe_tm-financial-trxn.php?work_id=${task.work_sys_id}&task_id=${task.sys_id}`;
                card.className = "group bg-white rounded-lg shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-200 overflow-hidden flex flex-col h-full hover:-translate-y-1 hover:border-blue-300 cursor-pointer";

                // Determine category color and text
                let categoryColor = 'gray';
                let categoryText = 'Unknown';
                let categoryIcon = 'fas fa-question-circle';

                if (task.category == 1) {
                    categoryColor = 'blue';
                    categoryText = 'Air Ticket Issue';
                    categoryIcon = 'fas fa-plane';
                } else if (task.category == 2) {
                    categoryColor = 'green';
                    categoryText = 'Hotel Booking';
                    categoryIcon = 'fas fa-hotel';
                }

                card.innerHTML = `
            <div class="p-4 flex-grow">
                <!-- Date and ID -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-gray-900 bg-gray-100 px-3 py-1 rounded-full task-id">
                            #${task.sys_id || 'N/A'}
                        </span>
                        <span class="text-xs text-gray-500 task-date">
                            <i class="far fa-calendar mr-1"></i>${task.created_at || 'N/A'}
                        </span>
                    </div>
                    
                    <!-- Task Title -->
                    <h3 class="text-base font-bold text-gray-900 group-hover:text-blue-600 transition-colors truncate task-title">
                        ${task.sys_id || 'No Title'}
                    </h3>
                </div>

                <!-- Category Badge -->
                <div class="mb-4">
                    <div class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-${categoryColor}-100 text-${categoryColor}-800 border border-${categoryColor}-200 task-category">
                        <i class="${categoryIcon} mr-2 text-xs"></i>
                        ${categoryText}
                    </div>
                </div>

                <!-- Work Title -->
                <div class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center text-gray-700">
                        <i class="fas fa-briefcase text-gray-400 mr-3 text-sm"></i>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-gray-500 mb-1">Work Title</div>
                            <div class="text-sm font-medium truncate task-work">${task.work_title || 'Unknown'}</div>
                        </div>
                    </div>
                </div>

                <!-- File Info -->
                <div class="flex items-center p-3 bg-${categoryColor}-50 rounded-lg border border-${categoryColor}-100">
                    <i class="fas fa-folder text-${categoryColor}-500 mr-3"></i>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs text-${categoryColor}-700 mb-1">Files</div>
                        <div class="text-sm text-gray-900 truncate task-files">${task.file_info || 'Folder'}</div>
                    </div>
                </div>
            </div>

            <!-- Footer with action -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 group-hover:bg-${categoryColor}-50 transition-colors">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 group-hover:text-${categoryColor}-700 transition-colors">
                        Financial Transaction
                    </span>
                    <div class="flex items-center">
                        <i class="fas fa-calculator text-gray-400 group-hover:text-${categoryColor}-500 transition-colors mr-2"></i>
                        <i class="fas fa-arrow-right text-gray-400 group-hover:text-${categoryColor}-500 group-hover:translate-x-1 transition-all"></i>
                    </div>
                </div>
            </div>
        `;

                tasksContainer.appendChild(card);
            });
        }

        function searchTasks() {
            const searchTerm = taskSearchInput.value.toLowerCase().trim();
            const filters = Array.from(document.querySelectorAll('input[name="searchFilter"]:checked'))
                .map(checkbox => checkbox.value);

            if (searchTerm === '') {
                renderCards(allTasks);
                updateResultsInfo(allTasks.length, allTasks.length);
                searchResultCount.classList.add('hidden');
                return;
            }

            const filteredTasks = allTasks.filter(task => {
                let match = false;

                filters.forEach(filter => {
                    switch (filter) {
                        case 'id':
                            if (task.sys_id && task.sys_id.toLowerCase().includes(searchTerm)) match = true;
                            break;
                        case 'title':
                            if (task.sys_id && task.sys_id.toLowerCase().includes(searchTerm)) match = true;
                            break;
                        case 'category':
                            const categoryText = task.category == 1 ? 'air ticket issue' :
                                task.category == 2 ? 'hotel booking' : 'unknown';
                            if (categoryText.includes(searchTerm)) match = true;
                            break;
                        case 'work':
                            if (task.work_title && task.work_title.toLowerCase().includes(searchTerm)) match = true;
                            break;
                        case 'files':
                            if (task.file_info && task.file_info.toLowerCase().includes(searchTerm)) match = true;
                            break;
                    }
                });

                return match;
            });

            renderCards(filteredTasks);
            updateResultsInfo(allTasks.length, filteredTasks.length);

            // Update search result count
            if (filteredTasks.length > 0) {
                searchResultCount.textContent = `${filteredTasks.length} found`;
                searchResultCount.classList.remove('hidden');
            } else {
                searchResultCount.classList.add('hidden');
            }
        }

        function updateResultsInfo(total, filtered) {
            totalTasksCount.textContent = `${total} tasks`;

            if (total === filtered) {
                resultsInfo.classList.add('hidden');
            } else {
                resultsInfo.classList.remove('hidden');
                filteredTasksCount.textContent = `Showing ${filtered} of ${total}`;
            }
        }

        // Event Listeners
        taskSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchTasks, 300);
        });

        // Add keydown event for instant search on Enter
        taskSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                searchTasks();
            }
        });

        // Filter checkbox change event
        document.querySelectorAll('input[name="searchFilter"]').forEach(checkbox => {
            checkbox.addEventListener('change', searchTasks);
        });

        // Clear search button
        clearSearchBtn.addEventListener('click', () => {
            taskSearchInput.value = '';
            searchResultCount.classList.add('hidden');
            renderCards(allTasks);
            updateResultsInfo(allTasks.length, allTasks.length);
        });


        // Financial Transaction Against Task
        function reloadFinancialTable() {
            fetch(GET_FINANCIAL_STATEMENT_API)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;

                    const finStmts = data.finStmts;
                    renderFinTable(finStmts);
                })
        }

        const finTableBody = document.getElementById('finTableBody');

        function renderFinTable(list) {
            // আগের ডাটা মুছে ফেলা
            finTableBody.innerHTML = '';

            if (!list || list.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-6 py-4 text-center text-sm text-gray-500" colspan="6">
                        No record found
                    </td>
                `;
                finTableBody.appendChild(tr);
                return; // ekhane function sesh
            }

            list.forEach(finSingleEntry => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";

                // type normalize
                const type = (finSingleEntry.type || '').toLowerCase();

                let typeBadge = `
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                            UNKNOWN
                        </span>
                    `;

                if (type === 'debit') {
                    typeBadge = `
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                DEBIT
                            </span>
                        `;
                } else if (type === 'credit') {
                    typeBadge = `
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                CREDIT
                            </span>
                        `;
                }

                tr.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ${finSingleEntry.date || 'N/A'}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            ${finSingleEntry.purpose || 'No Data Found'}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${finSingleEntry.client_name || finSingleEntry.vendor_name || 'Unknown'}
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                        ${finSingleEntry.work_title || 'No Data Found'}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                        ${typeBadge}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${finSingleEntry.amount || '-'}
                        </td>
                    `;

                finTableBody.appendChild(tr);
            });

        }

        loadTasks();
        reloadFinancialTable();

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            initDragDrop();

            // Add keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // Ctrl+D to clear all files
                if (e.ctrlKey && e.key === 'd') {
                    e.preventDefault();
                    clearAllFiles();
                }
                // Escape to clear paste area
                if (e.key === 'Escape' && document.activeElement === pasteArea) {
                    pasteArea.value = '';
                }
            });

            // Add tooltips
            const fileInput = document.getElementById('fileInput');
            fileInput.title = 'Select files to upload (Ctrl+Click to select multiple)';

            // Add help text
            // const dragDropArea = document.getElementById('dragDropArea');
            // const helpText = document.createElement('p');
            // helpText.className = 'text-xs text-gray-500 mt-2';
            // helpText.textContent = 'Drag & drop files here or click to browse';
            // dragDropArea.appendChild(helpText);
        });
    </script>

</body>


</html>