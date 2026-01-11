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
        reader.onload = function (e) {
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
        reader.onload = function (e) {
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