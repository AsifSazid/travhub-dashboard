<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$getAllTasksApi = $ip_port . "api/tasks/all-tasks.php";
$storeTasksApi = $ip_port . "api/tasks/store.php";

$workId = $_GET['work_id'];

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
            <div class="grid grid-cols-6 gap-4">
                <div class="col-span-12 bg-white rounded-lg shadow p-4">
                    <div id="taskTab" class="tab-content">
                        <div class="flex border-b mb-6">
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
                                                <i class="fas fa-folder-open mr-1"></i> Browse Files
                                            </button>
                                        </div>

                                        <textarea id="pasteArea"
                                            placeholder="Paste content here"
                                            class="w-full h-36 p-2 border-2 border-dashed border-gray-300 rounded"></textarea>

                                        <div class="mt-4">
                                            <div class="flex justify-between items-center mb-2">
                                                <h4 class="text-sm font-medium">Dropped or Pasted Files</h4>
                                                <span id="fileCount" class="text-xs bg-gray-200 px-2 py-1 rounded">0 files</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="droppedFilesList" class="text-sm text-gray-500">
                                        No files added yet
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
                            </div>

                            <button type="submit" class="flex-1 px-4 py-2 mt-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">Submit</button>

                        </form>

                        <hr class="my-6">

                        <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Task Lists</h2>

                            <div class="overflow-x-auto table-container">
                                <table id="ledgerTable" class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task Title</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work Title</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ledgerTableBody" class="bg-white divide-y divide-gray-200">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">2025-11-29 02:06:58</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">Task One for Rony Maldives (13)</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Air Ticket</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Air Ticket</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Folder</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <a href="cwe_tm-financial-trxn.php?work_id=123&task_id=234">
                                                    <i class="fa-solid fa-calculator"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <hr class="my-6">

                        <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Transaction Ledger</h2>

                            <div class="overflow-x-auto table-container">
                                <table id="ledgerTable" class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client (ID)</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor (ID)</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dir</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider text-green-600">Deposit (IN)</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider text-red-600">Withdraw (OUT)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ledgerTableBody" class="bg-white divide-y divide-gray-200">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">2025-11-29 02:06:58</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">Best Western Plus Pearl Creek Hotel</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Client: Rony Maldives (13)</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Dubai Hotel 7th to 10th August/BestWesternPlusPearlCreekHotel_Dubai_7Aug-10Aug_ShahidulIslam.pdf</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-green-600">
                                                19200.00
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-600">
                                                0.00
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot id="ledgerTableFoot">
                                        <tr class="bg-gray-100 font-bold">
                                            <td colspan="4" class="px-6 py-4 text-right text-base text-gray-900">Total:</td>
                                            <td class="px-6 py-4 text-right text-base text-gray-900"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-base text-green-700">
                                                19200.00 </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-base text-red-700">
                                                0.00 </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const API_URL_FOR_ALL_TASKS = "<?php echo $getAllTasksApi; ?>";
        const API_URL_FOR_TASK_STORE = "<?php echo $storeTasksApi; ?>";

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
                        // loadTasks();
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
            const dragDropArea = document.getElementById('dragDropArea');
            const helpText = document.createElement('p');
            helpText.className = 'text-xs text-gray-500 mt-2';
            helpText.textContent = 'Drag & drop files here or click to browse';
            dragDropArea.appendChild(helpText);
        });
    </script>

</body>


</html>