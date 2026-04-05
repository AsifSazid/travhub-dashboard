<?php
    $api_file_explorer = $ip_port . "api/file-explorer.php";
?>
<style>
    .context-menu-item {
        cursor: pointer;
        margin: 6px 0;
        padding: 6px 10px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease-in-out;
        white-space: nowrap;
    }
    
    .context-menu-item i {
        transition: color 0.2s ease-in-out;
        flex-shrink: 0;
    }
    
    .context-menu-item:hover {
        background-color: #eff6ff;
        transform: translateX(3px);
    }
    
    .context-menu-item:hover i {
        color: #2563eb;
    }
    
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        overflow: auto;
        padding: 20px;
    }
    
    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        overflow: hidden;
        word-wrap: break-word;
        word-break: break-word;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 15px;
        min-height: 30px;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #333;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }
    
    .modal-body {
        padding: 15px 0;
        line-height: 1.6;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }
    
    .close-btn {
        cursor: pointer;
        font-size: 24px;
        color: #666;
        flex-shrink: 0;
        margin-left: 10px;
        line-height: 1;
    }
    
    .close-btn:hover {
        color: #333;
    }
    
    .btn {
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
        border: none;
        font-weight: 500;
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-primary {
        background: #3b82f6;
        color: white;
    }
    
    .btn-primary:hover {
        background: #2563eb;
    }
    
    .btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background: #d1d5db;
    }
    
    .btn-outline {
        background: transparent;
        color: #3b82f6;
        border: 1px solid #3b82f6;
    }
    
    .btn-outline:hover {
        background: #eff6ff;
    }
    
    .toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #333;
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        display: none;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 300px;
        word-wrap: break-word;
    }
    
    .file-item.selected {
        background-color: #eff6ff !important;
        border-color: #3b82f6 !important;
    }
    
    .clipboard-preview {
        max-width: 100%;
        max-height: 200px;
        margin: 10px auto;
        display: block;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .loading-spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        margin: 10px auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .pdf-preview {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        margin: 10px 0;
        overflow: hidden;
    }
    
    .file-type-badge {
        display: inline-block;
        padding: 4px 8px;
        background: #e9ecef;
        border-radius: 4px;
        font-size: 12px;
        margin: 5px 0;
        white-space: nowrap;
    }
    
    .unsupported-modal {
        background: white;
        padding: 20px;
        border-radius: 10px;
        width: 100%;
        max-width: 400px;
        overflow: hidden;
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 15px;
    }
    
    .action-button {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
        text-align: center;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .action-button i {
        margin-right: 8px;
        flex-shrink: 0;
    }
    
    .action-button.download {
        background: #3b82f6;
        color: white;
    }
    
    .action-button.download:hover {
        background: #2563eb;
    }
    
    .action-button.link {
        background: #10b981;
        color: white;
    }
    
    .action-button.link:hover {
        background: #059669;
    }
    
    .action-button.open {
        background: #8b5cf6;
        color: white;
    }
    
    .action-button.open:hover {
        background: #7c3aed;
    }
    
    .action-button.image {
        background: #f59e0b;
        color: white;
    }
    
    .action-button.image:hover {
        background: #d97706;
    }
    
    .file-info {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
        margin: 12px 0;
        border-left: 4px solid #3b82f6;
        font-size: 13px;
        overflow: hidden;
    }
    
    .file-info p {
        margin: 5px 0;
        word-break: break-word;
    }
    
    .file-icon-large {
        font-size: 48px;
        margin-bottom: 10px;
        line-height: 1;
    }
    
    .supported-info {
        background: #d1fae5;
        padding: 10px;
        border-radius: 6px;
        margin-top: 12px;
        font-size: 12px;
        border: 1px solid #a7f3d0;
        overflow: hidden;
    }
    
    .supported-info strong {
        display: block;
        margin-bottom: 5px;
        color: #065f46;
    }
    
    .supported-info ul {
        margin: 5px 0;
        padding-left: 18px;
    }
    
    .supported-info li {
        margin: 3px 0;
        word-break: break-word;
    }
    
    /* Text wrapping for long file names */
    .text-wrap {
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    
    .truncate-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .modal-file-name {
        font-size: 16px;
        font-weight: 600;
        margin: 10px 0;
        color: #333;
        word-break: break-word;
        overflow-wrap: break-word;
        max-width: 100%;
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
        .modal-content {
            padding: 15px;
            margin: 10px;
        }
        
        .modal-header h3 {
            font-size: 16px;
        }
        
        .action-button {
            padding: 8px 10px;
            font-size: 13px;
        }
        
        .file-icon-large {
            font-size: 36px;
        }
    }
    
    /* Custom modal styling */
    .custom-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        overflow: auto;
        padding: 20px;
    }
    
    .custom-modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        width: 100%;
        max-width: 400px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    /* Fix for context menu */
    #context-menu {
        max-width: 250px;
        overflow: hidden;
    }
    
    #context-menu .context-menu-item {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<!-- Desktop with File Explorer -->
<div id="workFiles" class="flex-1 p-5 h-full max-h-9/10">
    <div class="bg-white rounded-lg overflow-hidden flex flex-col h-full shadow-2xl border border-gray-200">
        <!-- Address Bar -->
        <div class="bg-gray-100 px-4 py-2 flex items-center gap-3 border-b border-gray-300">
            <button class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                onclick="FileExplorer.goBack()" id="btn-back" disabled>
                <i class="fas fa-arrow-left"></i>
            </button>
            <button class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                onclick="FileExplorer.goForward()" id="btn-forward" disabled>
                <i class="fas fa-arrow-right"></i>
            </button>
            <button class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                onclick="FileExplorer.goUp()" id="btn-up" disabled>
                <i class="fas fa-arrow-up"></i>
            </button>
            <!-- Breadcrumb -->
            <div class="flex-1 bg-gray-50 px-4 py-2 border-b border-gray-300">
                <div class="flex items-center gap-2 text-sm text-gray-600 flex-wrap" id="breadcrumb">
                    <!-- Breadcrumb will be generated dynamically -->
                </div>
            </div>
            <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" onclick="FileExplorer.refresh()" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-2" onclick="FileExplorer.createNewFolder()" title="New Folder">
                <i class="fas fa-plus"></i>
            </button>
            <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 ml-2" onclick="FileExplorer.showUploadModal()" title="Upload">
                <i class="fas fa-upload"></i>
            </button>
        </div>

        <!-- Main Content -->
        <div class="flex flex-1 overflow-hidden">
            <!-- Main File Area -->
            <div class="flex-1 p-5 overflow-y-auto">
                <!-- Files Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4" id="files-container">
                    <!-- Files will be loaded here dynamically -->
                    <div class="col-span-full text-center py-10">
                        <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mb-3"></i>
                        <p class="text-gray-600">Loading files...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Bar -->
        <div class="bg-blue-500 text-white px-4 py-1 text-sm flex justify-between" id="status-bar">
            <div id="status-text">Loading...</div>
            <div id="folder-info"></div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<!-- Context Menu -->
<div class="fixed bg-white border border-gray-300 rounded shadow-xl z-50 hidden" id="context-menu">
    <div class="py-2 px-4 min-w-[180px]">
        <div class="context-menu-item" onclick="FileExplorer.contextOpen()">
            <i class="fas fa-folder-open w-5 mr-2 text-blue-500"></i> Open
        </div>
        <div class="border-t border-gray-300 my-1"></div>
        <div class="context-menu-item" onclick="FileExplorer.contextCut()">
            <i class="fas fa-cut w-5 mr-2 text-blue-500"></i> Cut
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextCopyPath()">
            <i class="fas fa-copy w-5 mr-2 text-blue-500"></i> Copy Path
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextCopyFileToClipboard()">
            <i class="fas fa-paste w-5 mr-2 text-blue-500"></i> Copy File to Clipboard
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextMove()">
            <i class="fas fa-right-left w-5 mr-2 text-blue-500"></i> Move
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextDuplicate()">
            <i class="fas fa-clone w-5 mr-2 text-blue-500"></i> Duplicate
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextRename()">
            <i class="fas fa-pen w-5 mr-2 text-blue-500"></i> Rename
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextDelete()">
            <i class="fas fa-trash-alt w-5 mr-2 text-red-500"></i> Delete
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextProperties()">
            <i class="fas fa-info-circle w-5 mr-2 text-blue-500"></i> Properties
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="upload-modal">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4 text-gray-800">Upload File</h3>
        <input type="file" id="file-upload" class="w-full p-2 border border-gray-300 rounded bg-gray-50 mb-4 text-gray-700" multiple>
        <div class="flex justify-end gap-3">
            <button class="btn btn-secondary" onclick="FileExplorer.closeUploadModal()">Cancel</button>
            <button class="btn btn-primary" onclick="FileExplorer.uploadFiles()">Upload</button>
        </div>
    </div>
</div>

<!-- Rename Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="rename-modal">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4 text-gray-800">Rename</h3>
        <input type="text" id="rename-input" class="w-full p-2 border border-gray-300 rounded bg-gray-50 mb-4 text-gray-700" placeholder="Enter new name">
        <div class="flex justify-end gap-3">
            <button class="btn btn-secondary" onclick="FileExplorer.closeRenameModal()">Cancel</button>
            <button class="btn btn-primary" onclick="FileExplorer.confirmRename()">Rename</button>
        </div>
    </div>
</div>

<!-- Properties Modal -->
<div id="propertyModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-semibold">Item Properties</h3>
            <span class="close-btn" onclick="FileExplorer.closeModal()">&times;</span>
        </div>
        <div id="modalBody" class="modal-body"></div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="FileExplorer.closeModal()">Close</button>
        </div>
    </div>
</div>

<!-- Clipboard Copy Modal -->
<div id="clipboardCopyModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-semibold">Copying to Clipboard</h3>
            <span class="close-btn" onclick="FileExplorer.closeClipboardModal()">&times;</span>
        </div>
        <div id="clipboardCopyBody" class="modal-body text-center">
            <div class="loading-spinner"></div>
            <p id="clipboardStatus">Loading file...</p>
            <div id="filePreview" class="mt-3"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="FileExplorer.closeClipboardModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
    const workId = `<?php echo $workId; ?>`;
    const SERVER_NAME = `<?php echo $_SESSION['scp']; ?>`;
    const API_FILE_EXPLORER = `<?php echo $api_file_explorer; ?>`;

    // File Explorer Singleton
    const FileExplorer = {
        state: {
            currentPath: '',
            clientFolder: '',
            workFolder: '',
            history: [],
            historyIndex: -1,
            selectedItem: null,
            contextItem: null,
            clipboardData: null,
            currentFileForAction: null,
            downloadBlob: null,
            downloadFilename: null,
            clipboardAction: null, // 'cut' or 'copy'
            clipboardItem: null,
            clipboardSourcePath: '',
            clipboardSourceName: '',
            moveItem: null,
            moveSourcePath: '',
            moveSourceName: '',
            selectedMovePath: null
        },
        
        config: {
            apiBaseUrl: `${API_FILE_EXPLORER}`,
            baseStoragePath: `/storage/clients/`,
            // baseStoragePath: `/${SERVER_NAME}/storage/clients/`,
            maxFileSizeForClipboard: 5 * 1024 * 1024 // 5MB limit
        },
        
        async init() {
            await this.loadFolder('');
            this.setupEventListeners();
            this.updateNavigationButtons();
        },
        
        async loadFolder(path = '') {
            try {
                this.showLoading(true);
                
                const response = await fetch(`${this.config.apiBaseUrl}?work_id=${workId}&action=list&path=${encodeURIComponent(path)}`);
                
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                
                const data = await response.json();
                
                if (data.success) {
                    console.log(data)
                    this.state.currentPath = data.currentPath || data.path || '';
                    this.state.clientFolder = data.clientFolder || '';
                    this.state.workFolder = data.workFolder || '';
                    
                    this.addToHistory(this.state.currentPath);
                    
                    if (data.contents && Array.isArray(data.contents)) {
                        this.renderFiles(data.contents);
                    } else {
                        this.renderFiles([]);
                    }
                    
                    this.updateBreadcrumb();
                    this.updateStatusBar(data.contents?.length || 0);
                } else {
                    this.showToast(data.error || 'Unknown error', 'error');
                }
            } catch (error) {
                console.error('Error loading folder:', error);
                this.showToast('Failed to load folder', 'error');
                this.renderFiles([]);
            } finally {
                this.showLoading(false);
            }
        },
        
        async navigateToFolder(folderName) {
            await this.loadFolder(this.state.currentPath ? `${this.state.currentPath}/${folderName}` : folderName);
        },
        
        async goBack() {
            if (this.state.historyIndex > 0) {
                this.state.historyIndex--;
                const path = this.state.history[this.state.historyIndex];
                await this.loadFolder(path);
            }
        },
        
        async goForward() {
            if (this.state.historyIndex < this.state.history.length - 1) {
                this.state.historyIndex++;
                const path = this.state.history[this.state.historyIndex];
                await this.loadFolder(path);
            }
        },
        
        async goUp() {
            if (this.state.currentPath) {
                const pathParts = this.state.currentPath.split('/').filter(p => p);
                pathParts.pop();
                const newPath = pathParts.join('/');
                await this.loadFolder(newPath);
            }
        },
        
        refresh() {
            this.loadFolder(this.state.currentPath);
        },
        
        async createNewFolder() {
            const folderName = prompt('Enter folder name:');
            if (!folderName?.trim()) {
                this.showToast('Folder name cannot be empty', 'error');
                return;
            }

            console.log(this.config.apiBaseUrl + `?work_id=${workId}`);

            try {
                const response = await fetch(this.config.apiBaseUrl + `?work_id=${workId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_folder',
                        path: this.state.currentPath,
                        name: folderName
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    await this.refresh();
                    this.showToast('Folder created successfully', 'success');
                } else {
                    this.showToast(data.error || 'Failed to create folder', 'error');
                }
            } catch (error) {
                this.showToast('Failed to create folder', 'error');
            }
        },
        
        renderFiles(files) {
            const container = document.getElementById('files-container');
            container.innerHTML = '';
            
            if (files.length === 0) {
                container.innerHTML = `
                    <div class="col-span-full text-center py-10">
                        <i class="fas fa-folder-open text-3xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600">This folder is empty</p>
                    </div>
                `;
                return;
            }
            
            files.forEach(file => {
                const fileElement = this.createFileElement(file);
                container.appendChild(fileElement);
            });
        },
        
        createFileElement(file) {
            const div = document.createElement('div');
            div.className = 'file-item cursor-pointer p-3 rounded-lg hover:bg-gray-50 border border-transparent hover:border-gray-200 transition-colors';
            div.dataset.name = file.name;
            div.dataset.type = file.type;
            div.dataset.path = file.path;
            
            const icon = this.getFileIcon(file);
            
            div.innerHTML = `
                <div class="flex justify-center mb-2">
                    <div class="text-3xl ${icon.color}">
                        <i class="${icon.class}"></i>
                    </div>
                </div>
                <div class="text-sm text-center text-gray-800 break-words max-w-[120px] truncate" title="${file.name}">
                    ${file.name}
                </div>
                <div class="text-xs text-gray-500 mt-1 text-center">${file.size}</div>
            `;
            
            div.addEventListener('click', (e) => this.handleFileClick(e, file, div));
            div.addEventListener('dblclick', () => this.handleFileDoubleClick(file));
            div.addEventListener('contextmenu', (e) => this.showContextMenu(e, file, div));
            
            return div;
        },
        
        getFileIcon(file) {
            const icons = {
                folder: { class: 'fas fa-folder', color: 'text-yellow-500' },
                image: { class: 'fas fa-file-image', color: 'text-green-500' },
                pdf: { class: 'fas fa-file-pdf', color: 'text-red-500' },
                document: { class: 'fas fa-file-word', color: 'text-blue-500' },
                spreadsheet: { class: 'fas fa-file-excel', color: 'text-green-600' },
                archive: { class: 'fas fa-file-archive', color: 'text-orange-500' },
                audio: { class: 'fas fa-file-audio', color: 'text-purple-500' },
                video: { class: 'fas fa-file-video', color: 'text-pink-500' },
                text: { class: 'fas fa-file-alt', color: 'text-gray-500' },
                file: { class: 'fas fa-file', color: 'text-gray-400' }
            };
            
            if (file.type === 'folder') return icons.folder;
            
            const ext = file.name.split('.').pop().toLowerCase();
            const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            const audioExts = ['mp3', 'wav', 'ogg', 'm4a'];
            const videoExts = ['mp4', 'avi', 'mov', 'wmv', 'flv'];
            const textExts = ['txt', 'md', 'json', 'xml', 'csv'];
            
            if (imageExts.includes(ext)) return icons.image;
            if (ext === 'pdf') return icons.pdf;
            if (audioExts.includes(ext)) return icons.audio;
            if (videoExts.includes(ext)) return icons.video;
            if (textExts.includes(ext)) return icons.text;
            if (['doc', 'docx'].includes(ext)) return icons.document;
            if (['xls', 'xlsx'].includes(ext)) return icons.spreadsheet;
            if (['zip', 'rar', '7z'].includes(ext)) return icons.archive;
            
            return icons.file;
        },
        
        handleFileClick(e, file, element) {
            document.querySelectorAll('.file-item').forEach(item => {
                item.classList.remove('selected', 'bg-blue-50', 'border-blue-200');
            });
            
            element.classList.add('selected', 'bg-blue-50', 'border-blue-200');
            this.state.selectedItem = file;
            
            document.getElementById('status-text').textContent = `1 item selected`;
        },
        
        handleFileDoubleClick(file) {
            if (file.type === 'folder') {
                this.navigateToFolder(file.name);
            } else {
                const fullPath = this.getFullFilePath(file);
                window.open(fullPath, '_blank');
            }
        },
        
        showContextMenu(e, file, element) {
            e.preventDefault();
            this.handleFileClick(e, file, element);
            
            const menu = document.getElementById('context-menu');
            menu.style.left = e.pageX + 'px';
            menu.style.top = e.pageY + 'px';
            menu.classList.remove('hidden');
            
            this.state.contextItem = file;
            this.state.currentFileForAction = file;
        },
        
        getFullFilePath(file) {
            return `${this.config.baseStoragePath}${this.state.clientFolder}/${this.state.workFolder}/${file.path}`;
        },
        
        // Main Copy File to Clipboard function
        async contextCopyFileToClipboard() {
            if (!this.state.contextItem) return;
            
            const file = this.state.contextItem;
            
            // Check if it's a folder
            if (file.type === 'folder') {
                this.showToast('Cannot copy folder to clipboard', 'error');
                this.hideContextMenu();
                return;
            }
            
            // Use smart copy function
            await this.smartCopyFileToClipboard();
            this.hideContextMenu();
        },
        
        // Smart copy function that handles different file types
        /*  async smartCopyFileToClipboard() {
                const file = this.state.contextItem;
                if (!file) return;
                
                const fileExtension = file.name.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(fileExtension);
                const isPDF = fileExtension === 'pdf';
                const isText = ['txt', 'md', 'json', 'xml', 'csv', 'html', 'htm'].includes(fileExtension);
                
                if (isImage) {
                    // Images can be copied directly
                    await this.copyImageToClipboard(file);
                } else if (isPDF) {
                    // For PDFs, show options modal
                    this.showPDFOptionsModal(file);
                } else if (isText) {
                    // For text files, read content and copy as text
                    await this.copyTextFileContent(file);
                } else {
                    // For other files, show unsupported modal
                    this.showUnsupportedFileModal(file);
                }
            },   */ 
        
        
        
        // estiak created //
        async smartCopyFileToClipboard() {
            const file = this.state.contextItem;
            if (!file) return;
            
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(fileExtension);
            
            // Extensions that should only copy the URL
            const isDocument = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'].includes(fileExtension);
        
            if (isImage) {
                // KEEP PREVIOUS MODEL FOR IMAGES: Copy image data directly
                await this.copyImageToClipboard(file);
            } else if (isDocument) {
                // FOR PDF/DOC/EXCEL: Directly copy the live URL only
                const fileUrl = this.getFullFilePath(file);
                const absoluteUrl = window.location.origin + fileUrl;
                
                try {
                    await navigator.clipboard.writeText(absoluteUrl);
                    this.showToast('File link copied to clipboard!', 'success');
                    // This ensures any open context menu or modal is closed immediately
                    this.hideContextMenu();
                } catch (err) {
                    console.error('Failed to copy link:', err);
                    this.showToast('Failed to copy link', 'error');
                }
            } else {
                // For other files, maintain your current unsupported logic or copy link as fallback
                const fileUrl = this.getFullFilePath(file);
                const absoluteUrl = window.location.origin + fileUrl;
                await navigator.clipboard.writeText(absoluteUrl);
                this.showToast('Link copied to clipboard', 'success');
            }
        },
        // estiak created  stop //
        
        async copyImageToClipboard(file) {
            this.showClipboardModal('Loading image...');
            
            try {
                const fileUrl = this.getFullFilePath(file);
                const response = await fetch(fileUrl);
                
                if (!response.ok) {
                    throw new Error(`Failed to fetch image: ${response.status}`);
                }
                
                const blob = await response.blob();
                
                // Check file size
                if (blob.size > this.config.maxFileSizeForClipboard) {
                    this.showToast(`Image too large (${this.formatFileSize(blob.size)}). Maximum ${this.formatFileSize(this.config.maxFileSizeForClipboard)} allowed.`, 'error');
                    this.closeClipboardModal();
                    return;
                }
                
                this.updateClipboardStatus('Copying image to clipboard...');
                
                // Check if Clipboard API is available
                if (navigator.clipboard && navigator.clipboard.write) {
                    try {
                        const clipboardItem = new ClipboardItem({
                            [blob.type]: blob
                        });
                        
                        await navigator.clipboard.write([clipboardItem]);
                        
                        this.updateClipboardStatus('Image copied successfully!');
                        this.showImagePreview(blob);
                        
                        setTimeout(() => {
                            this.closeClipboardModal();
                            this.showToast(`"${file.name}" copied to clipboard`, 'success');
                        }, 1500);
                        
                    } catch (clipboardError) {
                        console.error('Clipboard error:', clipboardError);
                        this.handleClipboardError(file, blob, clipboardError);
                    }
                } else {
                    this.updateClipboardStatus('Clipboard API not available');
                    this.showFallbackOptions(file, blob);
                }
                
            } catch (error) {
                console.error('Image copy error:', error);
                this.updateClipboardStatus(`Error: ${error.message}`);
                setTimeout(() => this.closeClipboardModal(), 2000);
                this.showToast('Failed to copy image', 'error');
            }
        },
        
        async copyTextFileContent(file) {
            this.showClipboardModal('Loading text file...');
            
            try {
                const fileUrl = this.getFullFilePath(file);
                const response = await fetch(fileUrl);
                
                if (!response.ok) {
                    throw new Error(`Failed to fetch file: ${response.status}`);
                }
                
                const text = await response.text();
                
                // Check file size
                if (text.length > this.config.maxFileSizeForClipboard) {
                    this.showToast(`Text file too large (${this.formatFileSize(text.length)}). Maximum ${this.formatFileSize(this.config.maxFileSizeForClipboard)} allowed.`, 'error');
                    this.closeClipboardModal();
                    return;
                }
                
                this.updateClipboardStatus('Copying text to clipboard...');
                
                await this.copyTextToClipboard(text);
                
                this.updateClipboardStatus('Text copied successfully!');
                
                setTimeout(() => {
                    this.closeClipboardModal();
                    this.showToast(`Text from "${file.name}" copied to clipboard`, 'success');
                }, 1500);
                
            } catch (error) {
                console.error('Text copy error:', error);
                this.updateClipboardStatus(`Error: ${error.message}`);
                setTimeout(() => this.closeClipboardModal(), 2000);
                this.showToast('Failed to copy text', 'error');
            }
        },
        
        showPDFOptionsModal(file) {
            const fileName = this.escapeHtml(file.name);
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const icon = this.getFileIcon(file);
            
            const modalContent = `
                <div class="custom-modal-content">
                    <div class="modal-header">
                        <h3>Copy PDF File</h3>
                        <span class="close-btn" onclick="FileExplorer.closeCustomModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="${icon.class} file-icon-large ${icon.color}"></i>
                            <div class="modal-file-name text-wrap">${fileName}</div>
                            <div class="file-type-badge">.${fileExtension.toUpperCase()}</div>
                            
                            <div class="file-info text-wrap">
                                <p><strong>Note:</strong> PDF files cannot be copied directly to clipboard in most browsers.</p>
                                <p class="mt-2">You can:</p>
                                <ul class="mt-1">
                                    <li>• Download the PDF file</li>
                                    <li>• Copy the file link</li>
                                    <li>• Open in browser</li>
                                </ul>
                            </div>
                            
                            <div class="action-buttons">
                                <button class="action-button download" onclick="FileExplorer.downloadFile('${fileName}')">
                                    <i class="fas fa-download"></i> Download PDF
                                </button>
                                
                                <button class="action-button link" onclick="FileExplorer.copyFileLink('${fileName}')">
                                    <i class="fas fa-link"></i> Copy PDF Link
                                </button>
                                
                                <button class="action-button open" onclick="FileExplorer.openFileInBrowser('${fileName}')">
                                    <i class="fas fa-external-link-alt"></i> Open in Browser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            this.showCustomModal(modalContent, 'pdf-options-modal');
        },
        
        showUnsupportedFileModal(file) {
            const fileName = this.escapeHtml(file.name);
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const icon = this.getFileIcon(file);
            
            const modalContent = `
                <div class="custom-modal-content">
                    <div class="modal-header">
                        <h3>File Type Not Supported</h3>
                        <span class="close-btn" onclick="FileExplorer.closeCustomModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="${icon.class} file-icon-large ${icon.color}"></i>
                            <div class="modal-file-name text-wrap">${fileName}</div>
                            <div class="file-type-badge">.${fileExtension.toUpperCase()}</div>
                            
                            <div class="file-info text-wrap">
                                <p><strong>Browser Limitation:</strong></p>
                                <p class="mt-1">.${fileExtension.toUpperCase()} files cannot be copied directly to clipboard. Most browsers only support:</p>
                                
                                <div class="supported-info text-wrap">
                                    <strong>Supported for direct copy:</strong>
                                    <ul>
                                        <li>Images (.jpg, .png, .gif, .webp, .svg)</li>
                                        <li>Text files (.txt, .md, .json)</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <button class="action-button download" onclick="FileExplorer.downloadFile('${fileName}')">
                                    <i class="fas fa-download"></i> Download File
                                </button>
                                
                                <button class="action-button link" onclick="FileExplorer.copyFileLink('${fileName}')">
                                    <i class="fas fa-link"></i> Copy File Link
                                </button>
                                
                                <button class="action-button open" onclick="FileExplorer.openFileInBrowser('${fileName}')">
                                    <i class="fas fa-external-link-alt"></i> Open in Browser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            this.showCustomModal(modalContent, 'unsupported-file-modal');
        },
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        showCustomModal(content, modalId) {
            // Close any existing modal
            this.closeCustomModal();
            
            const modal = document.createElement('div');
            modal.className = 'custom-modal';
            modal.innerHTML = content;
            modal.id = modalId;
            document.body.appendChild(modal);
            modal.style.display = 'flex';
            
            // Close modal when clicking outside [Tarek Vai told me to stop this!]
            // modal.addEventListener('click', (e) => {
            //     if (e.target === modal) {
            //         this.closeCustomModal();
            //     }
            // });
        },
        
        closeCustomModal() {
            const modals = ['pdf-options-modal', 'unsupported-file-modal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                    setTimeout(() => {
                        if (modal.parentNode) {
                            modal.parentNode.removeChild(modal);
                        }
                    }, 300);
                }
            });
        },
        
        async downloadFile(filename) {
            const file = this.state.currentFileForAction || this.state.contextItem;
            if (!file) return;
            
            const fileUrl = this.getFullFilePath(file);
            const a = document.createElement('a');
            a.href = fileUrl;
            a.download = file.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            this.closeCustomModal();
            this.showToast('Download started: ' + file.name, 'info');
        },
        
        async copyFileLink(filename) {
            const file = this.state.currentFileForAction || this.state.contextItem;
            if (!file) return;
            
            const fullPath = this.getFullFilePath(file);
            try {
                await this.copyTextToClipboard(fullPath);
                this.showToast('File link copied to clipboard', 'success');
            } catch (error) {
                this.showToast('Failed to copy link', 'error');
            }
            
            this.closeCustomModal();
        },
        
        openFileInBrowser(filename) {
            const file = this.state.currentFileForAction || this.state.contextItem;
            if (!file) return;
            
            const fullPath = this.getFullFilePath(file);
            window.open(fullPath, '_blank');
            this.closeCustomModal();
            this.showToast('Opening file in browser', 'info');
        },
        
        showClipboardModal(message) {
            const modal = document.getElementById('clipboardCopyModal');
            const status = document.getElementById('clipboardStatus');
            const preview = document.getElementById('filePreview');
            
            status.textContent = message;
            preview.innerHTML = '';
            modal.style.display = 'flex';
        },
        
        updateClipboardStatus(message) {
            const status = document.getElementById('clipboardStatus');
            status.textContent = message;
        },
        
        showImagePreview(blob) {
            const preview = document.getElementById('filePreview');
            const url = URL.createObjectURL(blob);
            preview.innerHTML = `
                <div class="bg-gray-100 p-4 rounded">
                    <p class="text-sm text-gray-600 mb-2">Preview:</p>
                    <img src="${url}" alt="Preview" class="clipboard-preview">
                    <p class="text-xs text-gray-500 mt-2 text-wrap">Image ready to paste</p>
                </div>
            `;
        },
        
        handleClipboardError(file, blob, error) {
            const preview = document.getElementById('filePreview');
            
            if (error.name === 'NotAllowedError' || error.message.includes('permission')) {
                preview.innerHTML = `
                    <div class="bg-red-50 p-4 rounded border border-red-200">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                            <h4 class="font-semibold text-red-700">Permission Denied</h4>
                        </div>
                        <p class="text-sm text-red-600 mb-3 text-wrap">
                            Clipboard access is blocked. Please allow clipboard permissions.
                        </p>
                        <button class="btn btn-primary w-full" onclick="FileExplorer.downloadAsFallback()">
                            <i class="fas fa-download mr-2"></i> Download Instead
                        </button>
                    </div>
                `;
            } else {
                preview.innerHTML = `
                    <div class="bg-yellow-50 p-4 rounded border border-yellow-200">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                            <h4 class="font-semibold text-yellow-700">Copy Failed</h4>
                        </div>
                        <p class="text-sm text-yellow-600 mb-3 text-wrap">
                            Could not copy to clipboard. Try downloading the file instead.
                        </p>
                        <div class="flex gap-2">
                            <button class="btn btn-secondary flex-1" onclick="FileExplorer.closeClipboardModal()">
                                Cancel
                            </button>
                            <button class="btn btn-primary flex-1" onclick="FileExplorer.downloadAsFallback()">
                                Download
                            </button>
                        </div>
                    </div>
                `;
            }
            
            this.state.downloadBlob = blob;
            this.state.downloadFilename = file.name;
        },
        
        showFallbackOptions(file, blob) {
            const preview = document.getElementById('filePreview');
            preview.innerHTML = `
                <div class="bg-blue-50 p-4 rounded border border-blue-200">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <h4 class="font-semibold text-blue-700">Clipboard Not Available</h4>
                    </div>
                    <p class="text-sm text-blue-600 mb-3 text-wrap">
                        Your browser doesn't support direct file copying.
                    </p>
                    <div class="space-y-2">
                        <button class="btn btn-primary w-full" onclick="FileExplorer.downloadAsFallback()">
                            <i class="fas fa-download mr-2"></i> Download File
                        </button>
                        <button class="btn btn-secondary w-full" onclick="FileExplorer.copyLinkAsFallback()">
                            <i class="fas fa-link mr-2"></i> Copy Link Instead
                        </button>
                    </div>
                </div>
            `;
            
            this.state.downloadBlob = blob;
            this.state.downloadFilename = file.name;
        },
        
        downloadAsFallback() {
            if (this.state.downloadBlob) {
                const url = URL.createObjectURL(this.state.downloadBlob);
                const a = document.createElement('a');
                a.href = url;
                a.download = this.state.downloadFilename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                this.showToast('File downloaded', 'info');
                this.closeClipboardModal();
            }
        },
        
        async copyLinkAsFallback() {
            const file = this.state.contextItem;
            if (!file) return;
            
            const fullPath = this.getFullFilePath(file);
            try {
                await this.copyTextToClipboard(fullPath);
                this.showToast('File link copied to clipboard', 'success');
            } catch (error) {
                this.showToast('Failed to copy link', 'error');
            }
            
            this.closeClipboardModal();
        },
        
        closeClipboardModal() {
            const modal = document.getElementById('clipboardCopyModal');
            if (modal) {
                modal.style.display = 'none';
            }
            // Clean up any blob URLs
            const preview = document.getElementById('filePreview');
            if (preview) {
                const images = preview.querySelectorAll('img');
                images.forEach(img => {
                    if (img.src.startsWith('blob:')) {
                        URL.revokeObjectURL(img.src);
                    }
                });
            }
        },
        
        // Copy Path function
        async contextCopyPath() {
            if (!this.state.contextItem) return;
            
            const fullPath = this.getFullFilePath(this.state.contextItem);
            try {
                await this.copyTextToClipboard(fullPath);
                this.showToast('File path copied to clipboard', 'success');
            } catch (error) {
                this.showToast('Failed to copy path', 'error');
            }
            
            this.hideContextMenu();
        },
        
        async copyTextToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (error) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    if (!successful) throw new Error('Copy command failed');
                    return true;
                } finally {
                    document.body.removeChild(textArea);
                }
            }
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        // Context Menu Actions
        contextOpen() {
            if (!this.state.contextItem) return;
            
            if (this.state.contextItem.type === 'folder') {
                this.navigateToFolder(this.state.contextItem.name);
            } else {
                window.open(this.getFullFilePath(this.state.contextItem), '_blank');
            }
            
            this.hideContextMenu();
        },
        
        async contextCut() {
            if (!this.state.contextItem) {
                this.showToast('No item selected', 'error');
                return;
            }
            
            this.state.clipboardAction = 'cut';
            this.state.clipboardItem = this.state.contextItem;
            this.state.clipboardSourcePath = this.state.currentPath;
            this.state.clipboardSourceName = this.state.contextItem.name;
            
            // Show paste button
            const pasteBtn = document.getElementById('paste-btn');
            if (pasteBtn) pasteBtn.classList.remove('hidden');
            
            this.showToast(`"${this.state.contextItem.name}" added to clipboard for moving`, 'info');
            this.hideContextMenu();
        },
        
        async contextCopy() {
            if (!this.state.contextItem) {
                this.showToast('No item selected', 'error');
                return;
            }
            
            this.state.clipboardAction = 'copy';
            this.state.clipboardItem = this.state.contextItem;
            this.state.clipboardSourcePath = this.state.currentPath;
            this.state.clipboardSourceName = this.state.contextItem.name;
            
            // Show paste button
            const pasteBtn = document.getElementById('paste-btn');
            if (pasteBtn) pasteBtn.classList.remove('hidden');
            
            this.showToast(`"${this.state.contextItem.name}" added to clipboard for copying`, 'info');
            this.hideContextMenu();
        },
        
        async pasteItem() {
            if (!this.state.clipboardItem || !this.state.clipboardAction) {
                this.showToast('No item in clipboard to paste', 'error');
                return;
            }
            
            const action = this.state.clipboardAction;
            const item = this.state.clipboardItem;
            
            if (action === 'cut') {
                // Move the item
                if (this.state.currentPath === this.state.clipboardSourcePath) {
                    this.showToast('Cannot paste in the same folder for cut operation', 'error');
                    return;
                }
                
                try {
                    const response = await fetch(this.config.apiBaseUrl + `?work_id=${workId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'move',
                            sourcePath: this.state.clipboardSourcePath,
                            sourceName: this.state.clipboardSourceName,
                            targetPath: this.state.currentPath,
                            targetName: item.name
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showToast('Item moved successfully', 'success');
                        
                        // Clear clipboard
                        this.state.clipboardAction = null;
                        this.state.clipboardItem = null;
                        this.state.clipboardSourcePath = '';
                        this.state.clipboardSourceName = '';
                        
                        // Hide paste button
                        const pasteBtn = document.getElementById('paste-btn');
                        if (pasteBtn) pasteBtn.classList.add('hidden');
                        
                        await this.refresh();
                    } else {
                        this.showToast(data.error || 'Failed to move item', 'error');
                    }
                } catch (error) {
                    this.showToast('Failed to move item', 'error');
                }
            } else if (action === 'copy') {
                // Copy the item
                try {
                    const response = await fetch(this.config.apiBaseUrl + `?work_id=${workId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'copy',
                            sourcePath: this.state.clipboardSourcePath,
                            sourceName: this.state.clipboardSourceName,
                            targetPath: this.state.currentPath,
                            targetName: item.name
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showToast('Item copied successfully', 'success');
                        await this.refresh();
                    } else {
                        this.showToast(data.error || 'Failed to copy item', 'error');
                    }
                } catch (error) {
                    this.showToast('Failed to copy item', 'error');
                }
            }
        },
        
        async contextMove() {
            if (!this.state.contextItem) {
                this.showToast('No item selected', 'error');
                return;
            }
            
            this.state.moveItem = this.state.contextItem;
            this.state.moveSourcePath = this.state.currentPath;
            this.state.moveSourceName = this.state.contextItem.name;
            
            // Load available folders
            await this.loadFoldersForMove();
            
            this.hideContextMenu();
        },
        
        async loadFoldersForMove() {
            try {
                const response = await fetch(`${this.config.apiBaseUrl}?work_id=${workId}&action=list_folders`);
                const data = await response.json();
                
                if (data.success && data.folders) {
                    // Show modal with folder selection
                    this.showMoveModal(data.folders);
                } else {
                    this.showToast('Failed to load folders', 'error');
                }
            } catch (error) {
                console.error('Error loading folders:', error);
                this.showToast('Failed to load folders', 'error');
            }
        },
        
        showMoveModal(folders) {
            const modalContent = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Move Item</h3>
                        <span class="close-btn" onclick="FileExplorer.closeMoveModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <p>Moving: <strong>${this.state.moveItem.name}</strong></p>
                            <p class="text-sm text-gray-600">From: ${this.state.currentPath || '/'}</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select destination:</label>
                            <div class="border border-gray-300 rounded p-3 max-h-40 overflow-y-auto" id="moveFoldersList">
                                <div class="space-y-2">
                                    <div class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer" 
                                         onclick="FileExplorer.selectMoveFolder('')">
                                        <i class="fas fa-folder text-yellow-500 mr-2"></i>
                                        <span>Root (/)</span>
                                    </div>
            `;
            
            folders.forEach(folder => {
                // Don't show current folder or its subfolders
                if (!folder.path.includes(this.state.currentPath + '/' + this.state.moveSourceName)) {
                    modalContent += `
                        <div class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer" 
                             onclick="FileExplorer.selectMoveFolder('${folder.path}')">
                            <i class="fas fa-folder text-yellow-500 mr-2"></i>
                            <span>${folder.name}</span>
                            <span class="text-xs text-gray-500 ml-2">${folder.path}</span>
                        </div>
                    `;
                }
            });
            
            modalContent += `
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">New name (optional):</label>
                            <input type="text" id="moveNewName" class="w-full p-2 border border-gray-300 rounded" 
                                   value="${this.state.moveItem.name}" placeholder="Enter new name">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="FileExplorer.closeMoveModal()">Cancel</button>
                        <button class="btn btn-primary" onclick="FileExplorer.confirmMove()">Move</button>
                    </div>
                </div>
            `;
            
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = modalContent;
            modal.id = 'move-modal';
            document.body.appendChild(modal);
            modal.style.display = 'flex';
        },
        
        closeMoveModal() {
            const modal = document.getElementById('move-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        },
        
        selectMoveFolder(path) {
            this.state.selectedMovePath = path;
            // Add visual feedback
            const items = document.querySelectorAll('#moveFoldersList > div');
            items.forEach(item => {
                item.classList.remove('bg-blue-50', 'border', 'border-blue-200');
            });
            event.currentTarget.classList.add('bg-blue-50', 'border', 'border-blue-200');
        },
        
        async confirmMove() {
            const newNameInput = document.getElementById('moveNewName');
            const newName = newNameInput ? newNameInput.value.trim() : this.state.moveItem.name;
            
            if (!this.state.selectedMovePath && this.state.selectedMovePath !== '') {
                this.showToast('Please select a destination folder', 'error');
                return;
            }
            
            try {
                const response = await fetch(this.config.apiBaseUrl + `?work_id=${workId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'move',
                        sourcePath: this.state.currentPath,
                        sourceName: this.state.moveSourceName,
                        targetPath: this.state.selectedMovePath,
                        targetName: newName
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showToast('Item moved successfully', 'success');
                    await this.refresh();
                    this.closeMoveModal();
                } else {
                    this.showToast(data.error || 'Failed to move item', 'error');
                }
            } catch (error) {
                this.showToast('Failed to move item', 'error');
            }
        },
        
        async contextDuplicate() {
            if (!this.state.contextItem) {
                this.showToast('No item selected', 'error');
                return;
            }
            
            const itemName = this.state.contextItem.name;
            const newName = prompt(`Enter new name for duplicate of "${itemName}":`, `${itemName} - Copy`);
            
            if (!newName) return;
            
            try {
                const response = await fetch(this.config.apiBaseUrl + `?work_id=${workId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'duplicate',
                        sourcePath: this.state.currentPath,
                        sourceName: itemName,
                        targetName: newName
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showToast('Item duplicated successfully', 'success');
                    await this.refresh();
                } else {
                    this.showToast(data.error || 'Failed to duplicate item', 'error');
                }
            } catch (error) {
                this.showToast('Failed to duplicate item', 'error');
            }
            
            this.hideContextMenu();
        },
        
        contextRename() {
            if (!this.state.contextItem) return;
            
            document.getElementById('rename-input').value = this.state.contextItem.name;
            document.getElementById('rename-modal').classList.remove('hidden');
            this.hideContextMenu();
        },
        
        async confirmRename() {
            const newName = document.getElementById('rename-input').value.trim();
            if (!newName) {
                this.showToast('Please enter a name', 'error');
                return;
            }
            
            // Check if same name
            if (newName === this.state.contextItem.name) {
                this.showToast('New name is same as old name', 'error');
                return;
            }
            
            try {
                console.log('Sending rename request...');
                console.log('Old name:', this.state.contextItem.name);
                console.log('New name:', newName);
                console.log('Path:', this.state.currentPath);
                
                const response = await fetch(this.config.apiBaseUrl + `?work_id=${workId}&action=rename`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'rename',
                        path: this.state.currentPath,
                        oldName: this.state.contextItem.name,
                        newName: newName
                    })
                });
                
                console.log('Response status:', response.status);
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success) {
                    this.showToast('Item renamed successfully', 'success');
                    await this.refresh();
                    this.closeRenameModal();
                } else {
                    this.showToast(data.error || 'Failed to rename item', 'error');
                }
            } catch (error) {
                console.error('Rename error:', error);
                this.showToast('Failed to rename item: ' + error.message, 'error');
            }
        },
        
        async contextDelete() {
            if (!this.state.contextItem) {
                this.showToast('No item selected', 'error');
                return;
            }
            
            const itemName = this.state.contextItem.name;
            
            if (!confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`)) {
                this.hideContextMenu();
                return;
            }
            
            try {
                const response = await fetch(this.config.apiBaseUrl + `?work_id=${workId}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        path: this.state.currentPath,
                        name: itemName
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showToast('Item deleted successfully', 'success');
                    await this.refresh();
                } else {
                    this.showToast(data.error || 'Failed to delete item', 'error');
                }
            } catch (error) {
                this.showToast('Failed to delete item', 'error');
            }
            
            this.hideContextMenu();
        },
        
        contextProperties() {
            if (!this.state.contextItem) return;
            
            const modal = document.getElementById('propertyModal');
            const body = document.getElementById('modalBody');
            
            body.innerHTML = `
                <div class="space-y-3">
                    <div><strong class="text-gray-700">Name:</strong> <span class="text-wrap">${this.state.contextItem.name}</span></div>
                    <div><strong class="text-gray-700">Type:</strong> ${this.state.contextItem.type}</div>
                    <div><strong class="text-gray-700">Size:</strong> ${this.state.contextItem.size || 'N/A'}</div>
                    <div><strong class="text-gray-700">Modified:</strong> ${this.state.contextItem.lastModified || 'N/A'}</div>
                    <div><strong class="text-gray-700">Path:</strong> <span class="text-xs text-wrap">${this.state.contextItem.path}</span></div>
                </div>
            `;
            
            modal.style.display = 'flex';
            this.hideContextMenu();
        },
        
        // UI Helper Functions
        updateBreadcrumb() {
            const breadcrumb = document.getElementById('breadcrumb');
            const parts = this.state.currentPath.split('/').filter(p => p);
            
            let html = `
                <span class="text-blue-600 cursor-pointer hover:underline truncate-text" onclick="FileExplorer.loadFolder('')">
                    <i class="fas fa-home mr-1"></i>${this.state.workFolder || 'Work'}
                </span>
            `;
            
            let currentPath = '';
            parts.forEach((part, index) => {
                currentPath += (currentPath ? '/' : '') + part;
                const isLast = index === parts.length - 1;
                
                html += `<i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>`;
                
                if (isLast) {
                    html += `<span class="text-gray-800 font-medium truncate-text">${part}</span>`;
                } else {
                    html += `
                        <span class="text-blue-600 cursor-pointer hover:underline truncate-text" 
                              onclick="FileExplorer.loadFolder('${currentPath}')">
                            ${part}
                        </span>
                    `;
                }
            });
            
            breadcrumb.innerHTML = html;
        },
        
        updateStatusBar(itemCount) {
            const statusText = document.getElementById('status-text');
            const folderInfo = document.getElementById('folder-info');
            
            statusText.textContent = `${itemCount} item${itemCount !== 1 ? 's' : ''}`;
            
            if (this.state.currentPath) {
                const parts = this.state.currentPath.split('/').filter(p => p);
                const currentFolder = parts.length > 0 ? parts[parts.length - 1] : this.state.workFolder;
                folderInfo.textContent = currentFolder || 'Work Folder';
            } else {
                folderInfo.textContent = this.state.workFolder || 'Work Folder';
            }
        },
        
        addToHistory(path) {
            this.state.history = this.state.history.slice(0, this.state.historyIndex + 1);
            this.state.history.push(path);
            this.state.historyIndex = this.state.history.length - 1;
            this.updateNavigationButtons();
        },
        
        updateNavigationButtons() {
            const btnBack = document.getElementById('btn-back');
            const btnForward = document.getElementById('btn-forward');
            const btnUp = document.getElementById('btn-up');
            
            btnBack.disabled = this.state.historyIndex <= 0;
            btnForward.disabled = this.state.historyIndex >= this.state.history.length - 1;
            btnUp.disabled = !this.state.currentPath;
        },
        
        showLoading(show) {
            const container = document.getElementById('files-container');
            if (show) {
                container.innerHTML = `
                    <div class="col-span-full text-center py-10">
                        <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mb-3"></i>
                        <p class="text-gray-600">Loading files...</p>
                    </div>
                `;
            }
        },
        
        showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                info: '#3b82f6',
                warning: '#f59e0b'
            };
            
            toast.style.backgroundColor = colors[type] || colors.info;
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        },
        
        hideContextMenu() {
            document.getElementById('context-menu').classList.add('hidden');
        },
        
        // Modal Functions
        showUploadModal() {
            document.getElementById('upload-modal').classList.remove('hidden');
        },
        
        closeUploadModal() {
            document.getElementById('upload-modal').classList.add('hidden');
        },
        
        closeRenameModal() {
            document.getElementById('rename-modal').classList.add('hidden');
        },
        
        closeModal() {
            document.getElementById('propertyModal').style.display = 'none';
        },
        
        async uploadFiles() {
            const input = document.getElementById('file-upload');
            if (input.files.length === 0) {
                this.showToast('Please select files to upload', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('path', this.state.currentPath);
            
            for (let i = 0; i < input.files.length; i++) {
                formData.append('files[]', input.files[i]);
            }
            
            try {
                const response = await fetch(this.config.apiBaseUrl + `?work_id=${workId}`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showToast(data.message || 'Files uploaded successfully', 'success');
                    await this.refresh();
                    this.closeUploadModal();
                } else {
                    this.showToast(data.error || 'Failed to upload files', 'error');
                }
            } catch (error) {
                this.showToast('Failed to upload files', 'error');
            }
        },
        
        // Event Listeners
        setupEventListeners() {
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#context-menu')) {
                    this.hideContextMenu();
                }
            });
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.hideContextMenu();
                    document.getElementById('upload-modal').classList.add('hidden');
                    document.getElementById('rename-modal').classList.add('hidden');
                    this.closeModal();
                    this.closeClipboardModal();
                    this.closeCustomModal();
                    this.closeMoveModal();
                }
                
                // Keyboard shortcuts
                if (e.ctrlKey && e.key === 'c' && this.state.selectedItem) {
                    e.preventDefault();
                    this.contextCopy();
                }
                
                if (e.ctrlKey && e.key === 'x' && this.state.selectedItem) {
                    e.preventDefault();
                    this.contextCut();
                }
                
                if (e.ctrlKey && e.key === 'v') {
                    e.preventDefault();
                    this.pasteItem();
                }
                
                if (e.key === 'Delete' && this.state.selectedItem) {
                    e.preventDefault();
                    this.contextDelete();
                }
                
                if (e.key === 'F2' && this.state.selectedItem) {
                    e.preventDefault();
                    this.contextRename();
                }
                
                if (e.ctrlKey && e.key === 'd' && this.state.selectedItem) {
                    e.preventDefault();
                    this.contextDuplicate();
                }
            });
            
            // Close modals on outside click
            window.addEventListener('click', (event) => {
                const modals = ['propertyModal', 'clipboardCopyModal', 'upload-modal', 'rename-modal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal && event.target === modal) {
                        if (modalId === 'propertyModal') {
                            this.closeModal();
                        } else if (modalId === 'clipboardCopyModal') {
                            this.closeClipboardModal();
                        } else if (modalId === 'upload-modal') {
                            this.closeUploadModal();
                        } else if (modalId === 'rename-modal') {
                            this.closeRenameModal();
                        }
                    }
                });
            });
        }
    };

    // Initialize File Explorer
    document.addEventListener('DOMContentLoaded', () => {
        FileExplorer.init();
    });
</script>