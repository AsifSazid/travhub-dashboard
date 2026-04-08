<?php
    $docStore = '../api/travelers/doc_store.php';
?>

<div class="bg-white rounded-lg shadow p-4 flex flex-col text-left">

    <div class="grid grid-cols-3 gap-4">
        <!-- Left -->
        <div class="col-span-2">
            <div class="mb-4">
                <h3 class="text-xl font-semibold mb-2">Files Are Shown here-</h3>
                <?php include('std-folders.php'); ?>
            </div>
        </div>


        <!-- Right -->
        <div class="col-span-1">
            <form id="docsForm">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Select Document
                    </label>

                    <select
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none">
                        <option value="" selected disabled>-- Select --</option>
                        <option value="nid">NID</option>
                        <option value="passports">Passports</option>
                        <option value="office_docs">Office Documents</option>
                        <option value="trade_license">Trade License</option>
                        <option value="trade_license_translated">Trade License (Translated and Notarized)</option>
                        <option value="company_letterhead">Company Letterhead</option>
                        <option value="common">Just Common</option>
                        <option value="moa">MOA</option>
                        <option value="form_xii">Form XII</option>
                        <option value="tin">TIN</option>
                        <option value="tax_return">Tax Return</option>
                        <option value="schedule_x">Schedule-X</option>
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

                <button type="submit" class="flex-1 px-4 py-2 mt-4 bg-blue-500 text-white rounded hover:bg-blue-600 transition-all duration-300 flex items-center justify-center">Submit</button>

            </form>
        </div>
    </div>

</div>


<script>
    const API_URL_FOR_DOC_STORE = '<?php echo $docStore; ?>?traveler_id=<? echo $travelerId ?>';

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
        // Modified version with proper event handling
        dragDropArea.addEventListener('click', (e) => {
            // Check if the click is directly on the area (not on any child elements)
            if (e.currentTarget === e.target) {
                fileInput.click();
            }
        });
        
        // Browse button handler
        // browseButton.addEventListener('click', (e) => {
        //     e.preventDefault();
        //     e.stopPropagation();
        //     fileInput.click();
        // });
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
    
    const form = document.getElementById('docsForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent page reload

        // Create FormData
        const formData = new FormData();

        const pasteArea = document.getElementById('pasteArea').value;

        formData.append('pasted_text', pasteArea);

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
        fetch(API_URL_FOR_DOC_STORE, {
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
                    alert('Docs saved successfully!');
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

                } else {
                    alert(data.message || 'Error:', 'Something went wrong'); 
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Server or network error. Please try again.'+ err.message);
               
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
    });

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
    
    document.addEventListener('DOMContentLoaded', function () {
        initDragDrop();
    });
</script>