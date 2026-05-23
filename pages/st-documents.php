<?php
    // $docStore = '../api/travelers/doc_store.php';
    $docStore = '../api/travelers/document_store_v2.php';
?>

<div class="bg-white rounded-lg shadow px-4 flex flex-col text-left">
    
    <button class="bg-green-500 text-white px-4 py-2 my-2 rounded hover:bg-green-600" onclick="showFileUploadModal()" title="File Upload"><i class="fas fa-upload mr-2"></i> Upload File/s</button>

    <div class="mb-4">
        <!--<h3 class="text-xl font-semibold mb-2">Files Are Shown here-</h3>-->
        <?php include('std-folders.php'); ?>
    </div>

</div>

<!-- Upload Modal -->
<div class="fixed inset-0 bg-white z-50 hidden flex flex-col" id="file-upload-modal">
    
    <div class="flex items-center justify-between p-4 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">File Upload</h3>
        <button onclick="closeFileUploadModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <form id="docsForm" class="flex-1 overflow-y-auto p-6 md:p-10">
        <div class="max-w-4xl mx-auto"> <div class="mb-6">
                <label class="block mb-2 text-lg font-medium text-gray-700">
                    Select Document
                </label>
                <select
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none">
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

            <label class="block text-lg font-medium text-gray-700 my-4">Upload or Paste Your Documents</label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div id="dragDropArea" class="rounded-xl border-2 border-dashed border-blue-300 p-10 flex flex-col items-center justify-center hover:bg-blue-50 transition-colors cursor-pointer">
                    <i class="fas fa-cloud-upload-alt text-6xl text-blue-400 mb-4"></i>
                    <p class="mb-4 text-gray-600">Drag and drop files here</p>
                    <input type="file" id="fileInput" multiple class="hidden">
                    <button
                        type="button"
                        onclick="document.getElementById('fileInput').click()"
                        class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 shadow-md">
                        <i class="fas fa-folder-open mr-2"></i> Browse Files
                    </button>
                </div>

                <textarea id="pasteArea"
                    placeholder="Paste your content or text data here..."
                    class="w-full h-64 md:h-full min-h-[250px] p-4 border-2 border-dashed border-gray-300 rounded-xl focus:border-blue-500 outline-none"></textarea>
            </div>

            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="font-semibold text-gray-700">Dropped or Pasted Files</h4>
                    <span id="fileCount" class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold">0 files</span>
                </div>
                <div id="droppedFilesList" class="text-gray-500 italic">
                    No files added yet
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-10 mb-10">
                <button type="button" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium transition-all" onclick="closeFileUploadModal()">Cancel</button>
                <button type="submit" class="px-10 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-lg transition-all">Upload Everything</button>
            </div>
        </div>
    </form>
</div>

<script>
    const API_URL_FOR_DOC_STORE = '<?php echo $docStore; ?>?traveler_id=<? echo $travelerId ?>';

    // File Management Variables
    let droppedFiles = [];
    let pastedItems = [];
    
    function showFileUploadModal() {
        document.getElementById('file-upload-modal').classList.remove('hidden');
    }
    
    // Modal Functions
    function showFileUploadModal() {
        document.getElementById('file-upload-modal').classList.remove('hidden');
    }
    
    function closeFileUploadModal() {
        document.getElementById('file-upload-modal').classList.add('hidden');
    }

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
    
    // Add this HTML for conversion status display - put it near the upload button
    // Add this div somewhere in your modal:
    // <div id="conversionStatus" class="hidden mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg"></div>
    
    // Replace the existing form submit handler with this:
    const form = document.getElementById('docsForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
    
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
    
        // Show loading state with detailed status
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing files (PDFs will be converted to images)...';
        submitBtn.disabled = true;
        
        // Show conversion status area
        const statusDiv = document.getElementById('conversionStatus') || createStatusDiv();
        statusDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-sync-alt fa-spin mr-2"></i> Uploading and converting files...</div>';
        statusDiv.classList.remove('hidden');
    
        // Send to API
        fetch(API_URL_FOR_DOC_STORE, {
            method: 'POST',
            body: formData
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
                // Show conversion details if any PDFs were converted
                if (data.conversions && data.conversions.length > 0) {
                    let conversionHtml = '<div class="text-sm"><i class="fas fa-file-image text-green-600 mr-2"></i> <strong>PDF Conversions:</strong><ul class="mt-2 ml-6 list-disc">';
                    data.conversions.forEach(conv => {
                        conversionHtml += `<li>${conv.original_pdf} → ${conv.converted_to} (Page ${conv.page})</li>`;
                    });
                    conversionHtml += '</ul></div>';
                    statusDiv.innerHTML = conversionHtml;
                    statusDiv.className = 'mt-4 p-3 bg-green-50 border border-green-200 rounded-lg';
                    
                    // Auto hide after 5 seconds
                    setTimeout(() => {
                        statusDiv.classList.add('hidden');
                    }, 5000);
                } else {
                    statusDiv.innerHTML = '<div class="text-sm"><i class="fas fa-check-circle text-green-600 mr-2"></i> ' + data.message + '</div>';
                    statusDiv.className = 'mt-4 p-3 bg-green-50 border border-green-200 rounded-lg';
                    setTimeout(() => {
                        statusDiv.classList.add('hidden');
                    }, 3000);
                }
                
                // Show warnings if any
                if (data.warnings && data.warnings.length > 0) {
                    let warningHtml = '<div class="text-sm text-yellow-700"><i class="fas fa-exclamation-triangle mr-2"></i> <strong>Warnings:</strong><ul class="mt-2 ml-6 list-disc">';
                    data.warnings.forEach(warning => {
                        warningHtml += `<li>${warning}</li>`;
                    });
                    warningHtml += '</ul></div>';
                    
                    // Append warnings below success message
                    const warningDiv = document.createElement('div');
                    warningDiv.innerHTML = warningHtml;
                    warningDiv.className = 'mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg';
                    statusDiv.appendChild(warningDiv);
                }
                
                alert('✅ ' + data.message);
                
                // Reset form
                form.reset();
                droppedFiles = [];
                document.getElementById('droppedFilesList').innerHTML = `
                    <div class="text-center text-gray-500 py-4 text-sm">
                        <i class="fas fa-file mb-1"></i>
                        <p>No files added yet</p>
                    </div>
                `;
                updateFileCount();
                document.getElementById('pasteArea').value = '';
    
            } else {
                statusDiv.innerHTML = '<div class="text-sm text-red-600"><i class="fas fa-exclamation-circle mr-2"></i> ' + (data.message || 'Upload failed') + '</div>';
                statusDiv.className = 'mt-4 p-3 bg-red-50 border border-red-200 rounded-lg';
                alert('❌ Error: ' + (data.message || 'Something went wrong'));
            }
        })
        .catch(err => {
            console.error('Error:', err);
            statusDiv.innerHTML = '<div class="text-sm text-red-600"><i class="fas fa-exclamation-circle mr-2"></i> Server error: ' + err.message + '</div>';
            statusDiv.className = 'mt-4 p-3 bg-red-50 border border-red-200 rounded-lg';
            alert('Server or network error. Please try again.\n' + err.message);
        })
        .finally(() => {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Hide status after 8 seconds if not already hidden
            setTimeout(() => {
                if (statusDiv && !statusDiv.classList.contains('hidden')) {
                    statusDiv.classList.add('hidden');
                }
            }, 8000);
        });
    });
    
    // Helper function to create status div if it doesn't exist
    function createStatusDiv() {
        let statusDiv = document.getElementById('conversionStatus');
        if (!statusDiv) {
            statusDiv = document.createElement('div');
            statusDiv.id = 'conversionStatus';
            statusDiv.className = 'hidden mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg';
            // Insert it after the submit button or in a suitable place
            const form = document.getElementById('docsForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.parentNode.insertBefore(statusDiv, submitBtn.nextSibling);
        }
        return statusDiv;
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
    
    document.addEventListener('DOMContentLoaded', function () {
        initDragDrop();
    });
</script>