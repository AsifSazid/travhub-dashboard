    <!-- Desktop with File Explorer -->
    <div id="workFiles" class="flex-1 p-5 h-full max-h-9/10">
        <div class="bg-gray-800 rounded-lg overflow-hidden flex flex-col h-full shadow-2xl border border-gray-700">
            <!-- Address Bar -->
            <div class="bg-gray-900 px-4 py-2 flex items-center gap-3 border-b border-gray-700">
                <button class="text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                    onclick="goBack()" id="btn-back" disabled>
                    <i class="fas fa-arrow-left"></i>
                </button>
                <button class="text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                    onclick="goForward()" id="btn-forward" disabled>
                    <i class="fas fa-arrow-right"></i>
                </button>
                <button class="text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                    onclick="goUp()" id="btn-up" disabled>
                    <i class="fas fa-arrow-up"></i>
                </button>
                <!-- Breadcrumb -->
                <div class="flex-1 bg-gray-800 px-4 py-2 border-b border-gray-700">
                    <div class="flex items-center gap-2 text-sm text-gray-400 flex-wrap" id="breadcrumb">
                        <!-- Breadcrumb will be generated dynamically -->
                    </div>
                </div>
                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" onclick="refreshFolder()" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 ml-2" onclick="createNewFolder()" title="New Folder">
                    <i class="fas fa-plus"></i>
                </button>
                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 ml-2" onclick="showUploadModal()" title="Upload">
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
                            <i class="fas fa-spinner fa-spin text-3xl text-blue-400 mb-3"></i>
                            <p class="text-gray-400">Loading files...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Bar -->
            <div class="bg-blue-600 text-white px-4 py-1 text-sm flex justify-between" id="status-bar">
                <div id="status-text">Loading...</div>
                <div id="folder-info"></div>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div class="fixed bg-gray-800 border border-gray-600 rounded shadow-xl z-50 hidden" id="context-menu">
        <div class="py-2 px-4 min-w-[180px]">
            <div class="context-menu-item" onclick="contextOpen()">
                <i class="fas fa-folder-open w-5 mr-2 text-blue-400"></i> Open
            </div>
            <!-- <div class="context-menu-item" onclick="contextOpenInNewTab()">
                <i class="fas fa-external-link-alt w-5 mr-2 text-blue-400"></i> Open in new tab
            </div> -->
            <div class="border-t border-gray-600 my-1"></div>
            <div class="context-menu-item" onclick="contextCopy()">
                <i class="fas fa-copy w-5 mr-2 text-blue-400"></i> Copy
            </div>
            <div class="context-menu-item" onclick="contextDuplicate()">
                <i class="fas fa-copy w-5 mr-2 text-blue-400"></i> Duplicate
            </div>
            <div class="context-menu-item" onclick="contextRename()">
                <i class="fas fa-edit w-5 mr-2 text-blue-400"></i> Rename
            </div>
            <div class="context-menu-item" onclick="contextDelete()">
                <i class="fas fa-trash-alt w-5 mr-2 text-red-400"></i> Delete
            </div>
            <div class="border-t border-gray-600 my-1"></div>
            <div class="context-menu-item" onclick="contextProperties()">
                <i class="fas fa-info-circle w-5 mr-2 text-blue-400"></i> Properties
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="upload-modal">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Upload File</h3>
            <input type="file" id="file-upload" class="w-full p-2 border border-gray-600 rounded bg-gray-700 mb-4" multiple>
            <div class="flex justify-end gap-3">
                <button class="px-4 py-2 bg-gray-700 rounded hover:bg-gray-600" onclick="closeUploadModal()">Cancel</button>
                <button class="px-4 py-2 bg-blue-600 rounded hover:bg-blue-700" onclick="uploadFiles()">Upload</button>
            </div>
        </div>
    </div>

    <!-- Rename Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="rename-modal">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Rename</h3>
            <input type="text" id="rename-input" class="w-full p-2 border border-gray-600 rounded bg-gray-700 mb-4" placeholder="Enter new name">
            <div class="flex justify-end gap-3">
                <button class="px-4 py-2 bg-gray-700 rounded hover:bg-gray-600" onclick="closeRenameModal()">Cancel</button>
                <button class="px-4 py-2 bg-blue-600 rounded hover:bg-blue-700" onclick="confirmRename()">Rename</button>
            </div>
        </div>
    </div>

    <script>
        const workId = `<?php echo $workId; ?>`;

        // File Explorer State Management
        class FileExplorer {
            constructor() {
                this.currentPath = '';
                this.clientFolder = '';
                this.workFolder = '';
                this.history = [];
                this.historyIndex = -1;
                this.selectedItem = null;
                this.contextItem = null;
                this.apiBaseUrl = `../api/file-explorer.php`;
                this.initialize();
            }

            async initialize() {
                try {
                    await this.loadFolder('');
                    setInterval(() => this.updateTime(), 60000);
                    this.setupEventListeners();
                    this.updateNavigationButtons();
                } catch (error) {
                    console.error('Initialization failed:', error);
                    this.showError('Failed to initialize file explorer: ' + error.message);
                }
            }

            async loadFolder(path = '') {
                try {
                    console.log('Loading folder:', path);
                    this.showLoading(true);

                    const response = await fetch(`${this.apiBaseUrl}?work_id=${workId}&action=list&path=${encodeURIComponent(path)}`);

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();
                    console.log('API Response:', data);

                    if (data.success) {
                        // Store folder information
                        this.currentPath = data.currentPath || data.path || '';
                        this.clientFolder = data.clientFolder || '';
                        this.workFolder = data.workFolder || '';

                        this.addToHistory(this.currentPath);

                        // Check if contents exists and is array
                        if (data.contents && Array.isArray(data.contents)) {
                            this.renderFiles(data.contents);
                        } else {
                            console.warn('No contents array in response:', data);
                            this.renderFiles([]);
                        }

                        this.updateBreadcrumb();
                        this.updateStatusBar(data.contents?.length || 0);
                    } else {
                        this.showError(data.error || 'Unknown error');
                        this.renderFiles([]);
                    }
                } catch (error) {
                    console.error('Error loading folder:', error);
                    this.showError('Failed to load folder: ' + error.message);
                    this.renderFiles([]);
                } finally {
                    this.showLoading(false);
                }
            }

            async navigateToFolder(folderName) {
                await this.loadFolder(this.currentPath ? `${this.currentPath}/${folderName}` : folderName);
            }

            async goBack() {
                if (this.historyIndex > 0) {
                    this.historyIndex--;
                    const path = this.history[this.historyIndex];
                    await this.loadFolder(path);
                }
            }

            async goForward() {
                if (this.historyIndex < this.history.length - 1) {
                    this.historyIndex++;
                    const path = this.history[this.historyIndex];
                    await this.loadFolder(path);
                }
            }

            async goUp() {
                if (this.currentPath) {
                    const pathParts = this.currentPath.split('/').filter(p => p);
                    pathParts.pop();
                    const newPath = pathParts.join('/');
                    await this.loadFolder(newPath);
                }
            }

            async createFolder(folderName) {
                if (!folderName || folderName.trim() === '') {
                    this.showError('Folder name cannot be empty');
                    return;
                }

                try {
                    const response = await fetch(this.apiBaseUrl + `?work_id=${workId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'create_folder',
                            path: this.currentPath,
                            name: folderName
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        await this.loadFolder(this.currentPath);
                        this.showMessage('Folder created successfully');
                    } else {
                        this.showError(data.error || 'Failed to create folder');
                    }
                } catch (error) {
                    this.showError('Failed to create folder: ' + error.message);
                }
            }

            async renameItem(oldName, newName) {
                if (!newName || newName.trim() === '') {
                    this.showError('New name cannot be empty');
                    return;
                }

                try {
                    const response = await fetch(this.apiBaseUrl + `?work_id=${workId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'rename',
                            path: this.currentPath,
                            oldName: oldName,
                            newName: newName
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        await this.loadFolder(this.currentPath);
                        this.showMessage('Item renamed successfully');
                    } else {
                        this.showError(data.error || 'Failed to rename item');
                    }
                } catch (error) {
                    this.showError('Failed to rename item: ' + error.message);
                }
            }

            async deleteItem(itemName) {
                if (!confirm(`Are you sure you want to delete "${itemName}"?`)) {
                    return;
                }

                try {
                    const response = await fetch(this.apiBaseUrl + `?work_id=${workId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            path: this.currentPath,
                            name: itemName
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        await this.loadFolder(this.currentPath);
                        this.showMessage('Item deleted successfully');
                    } else {
                        this.showError(data.error || 'Failed to delete item');
                    }
                } catch (error) {
                    this.showError('Failed to delete item: ' + error.message);
                }
            }

            renderFiles(files) {
                const container = document.getElementById('files-container');
                container.innerHTML = '';

                if (files.length === 0) {
                    container.innerHTML = `
                <div class="col-span-full text-center py-10">
                    <i class="fas fa-folder-open text-3xl text-gray-500 mb-3"></i>
                    <p class="text-gray-400">This folder is empty</p>
                    <p class="text-gray-500 text-sm mt-2">No files or folders found</p>
                </div>
            `;
                    return;
                }

                files.forEach(file => {
                    const fileElement = this.createFileElement(file);
                    container.appendChild(fileElement);
                });
            }

            createFileElement(file) {
                const div = document.createElement('div');
                div.className = 'file-item';
                div.dataset.name = file.name;
                div.dataset.type = file.type;
                div.dataset.path = file.path;

                const icon = this.getFileIcon(file);

                div.innerHTML = `
            <div class="file-icon-large ${icon.color}">
                <i class="${icon.class}"></i>
            </div>
            <div class="text-sm text-center text-gray-200 break-words max-w-[120px] truncate" title="${file.name}">
                ${file.name}
            </div>
            <div class="text-xs text-gray-400 mt-1">${file.size}</div>
        `;

                // Add event listeners
                div.addEventListener('click', (e) => this.handleFileClick(e, file, div));
                div.addEventListener('dblclick', () => this.handleFileDoubleClick(file));
                div.addEventListener('contextmenu', (e) => this.showContextMenu(e, file, div));

                return div;
            }

            getFileIcon(file) {
                const baseIcons = {
                    folder: {
                        class: 'fas fa-folder',
                        color: 'text-yellow-500'
                    },
                    image: {
                        class: 'fas fa-file-image',
                        color: 'text-green-500'
                    },
                    pdf: {
                        class: 'fas fa-file-pdf',
                        color: 'text-red-500'
                    },
                    document: {
                        class: 'fas fa-file-word',
                        color: 'text-blue-500'
                    },
                    spreadsheet: {
                        class: 'fas fa-file-excel',
                        color: 'text-green-600'
                    },
                    archive: {
                        class: 'fas fa-file-archive',
                        color: 'text-orange-500'
                    },
                    file: {
                        class: 'fas fa-file',
                        color: 'text-gray-400'
                    }
                };

                return baseIcons[file.icon] || baseIcons.file;
            }

            handleFileClick(e, file, element) {
                // Clear previous selection
                document.querySelectorAll('.file-item').forEach(item => {
                    item.classList.remove('selected');
                });

                // Select current
                element.classList.add('selected');
                this.selectedItem = file;

                // Update status bar
                document.getElementById('status-text').textContent = `1 item selected`;
            }

            handleFileDoubleClick(file) {
                if (file.type === 'folder') {
                    this.navigateToFolder(file.name);
                } else {
                    // For files, you can implement file preview or download
                    // alert(`Opening file: ${file.name}`);
                    // In production: 
                    window.open(`/storage/clients/${this.clientFolder}/${this.workFolder}/${file.path}`);
                }
            }

            showContextMenu(e, file, element) {
                e.preventDefault();
                this.handleFileClick(e, file, element);

                const menu = document.getElementById('context-menu');
                menu.style.left = e.pageX + 'px';
                menu.style.top = e.pageY + 'px';
                menu.classList.remove('hidden');

                // Store context item
                this.contextItem = file;
            }

            updateBreadcrumb() {
                const breadcrumb = document.getElementById('breadcrumb');
                const parts = this.currentPath.split('/').filter(p => p);

                // Start with client and work folder
                let html = `
            <span class="text-blue-400 cursor-pointer hover:underline" onclick="fileExplorer.loadFolder('')">
                <i class="fas fa-home mr-1"></i>${this.workFolder || 'Work'}
            </span>
        `;

                let currentPath = '';
                parts.forEach((part, index) => {
                    currentPath += (currentPath ? '/' : '') + part;
                    const isLast = index === parts.length - 1;

                    html += `<i class="fas fa-chevron-right text-xs mx-2"></i>`;

                    if (isLast) {
                        html += `<span class="text-gray-300">${part}</span>`;
                    } else {
                        html += `
                    <span class="text-blue-400 cursor-pointer hover:underline" 
                          onclick="fileExplorer.loadFolder('${currentPath}')">
                        ${part}
                    </span>
                `;
                    }
                });

                breadcrumb.innerHTML = html;
            }

            updateStatusBar(itemCount) {
                const statusText = document.getElementById('status-text');
                const folderInfo = document.getElementById('folder-info');

                statusText.textContent = `${itemCount} item${itemCount !== 1 ? 's' : ''}`;

                // Show current folder name or work folder name
                if (this.currentPath) {
                    const parts = this.currentPath.split('/').filter(p => p);
                    const currentFolder = parts.length > 0 ? parts[parts.length - 1] : this.workFolder;
                    folderInfo.textContent = currentFolder || 'Work Folder';
                } else {
                    folderInfo.textContent = this.workFolder || 'Work Folder';
                }
            }

            addToHistory(path) {
                // Remove any forward history
                this.history = this.history.slice(0, this.historyIndex + 1);
                this.history.push(path);
                this.historyIndex = this.history.length - 1;

                // Update navigation buttons
                this.updateNavigationButtons();
            }

            updateNavigationButtons() {
                const btnBack = document.getElementById('btn-back');
                const btnForward = document.getElementById('btn-forward');
                const btnUp = document.getElementById('btn-up');

                btnBack.disabled = this.historyIndex <= 0;
                btnForward.disabled = this.historyIndex >= this.history.length - 1;
                btnUp.disabled = !this.currentPath;
            }

            showLoading(show) {
                if (show) {
                    document.getElementById('files-container').innerHTML = `
                <div class="col-span-full text-center py-10">
                    <i class="fas fa-spinner fa-spin text-3xl text-blue-400 mb-3"></i>
                    <p class="text-gray-400">Loading files...</p>
                </div>
            `;
                }
            }

            showError(message) {
                console.error('File Explorer Error:', message);
                alert('Error: ' + message);
            }

            showMessage(message) {
                console.log('File Explorer Message:', message);
                alert(message);
            }

            setupEventListeners() {
                // Close context menu on click outside
                document.addEventListener('click', () => {
                    document.getElementById('context-menu').classList.add('hidden');
                });

                // Handle escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        document.getElementById('context-menu').classList.add('hidden');
                        document.getElementById('upload-modal').classList.add('hidden');
                        document.getElementById('rename-modal').classList.add('hidden');
                    }
                });
            }
        }

        // Context Menu Actions
        function contextOpen() {
            if (window.fileExplorer?.contextItem) {
                const item = window.fileExplorer.contextItem;

                if (item.type === 'folder') {
                    window.fileExplorer.navigateToFolder(item.name);
                } else {
                    // Construct the full file path
                    const fullPath = `/storage/clients/${window.fileExplorer.clientFolder}/${window.fileExplorer.workFolder}/${item.path}`;
                    window.open(fullPath);
                }
            }
        }

        function contextOpenInNewTab() {
            if (window.fileExplorer?.contextItem) {
                const item = window.fileExplorer.contextItem;
                const fullPath = `/storage/clients/${window.fileExplorer.clientFolder}/${window.fileExplorer.workFolder}/${item.path}`;
                window.open(fullPath, '_blank');
            }
        }

        async function contextCopy() {
            const item = window.fileExplorer?.contextItem;
            if (!item) return;

            const fullPath = `${location.origin}/storage/clients/${window.fileExplorer.clientFolder}/${window.fileExplorer.workFolder}/${item.path}`;

            try {
                await navigator.clipboard.writeText(fullPath);
                alert('File link copied to clipboard');
            } catch (err) {
                console.error('Clipboard error:', err);
                alert('Clipboard access denied');
            }
        }

        function contextDuplicate() {

        }

        function contextRename() {
            if (window.fileExplorer?.contextItem) {
                document.getElementById('rename-input').value = window.fileExplorer.contextItem.name;
                document.getElementById('rename-modal').classList.remove('hidden');
            }
        }

        function contextDelete() {
            if (window.fileExplorer?.contextItem) {
                window.fileExplorer.deleteItem(window.fileExplorer.contextItem.name);
            }
        }

        function contextProperties() {
            if (window.fileExplorer?.contextItem) {
                const item = window.fileExplorer.contextItem;
                alert(`Properties:\nName: ${item.name}\nType: ${item.type}\nSize: ${item.size}\nModified: ${item.lastModified}`);
            }
        }

        // Modal Functions
        function showUploadModal() {
            document.getElementById('upload-modal').classList.remove('hidden');
        }

        function closeUploadModal() {
            document.getElementById('upload-modal').classList.add('hidden');
        }

        function closeRenameModal() {
            document.getElementById('rename-modal').classList.add('hidden');
        }

        async function uploadFiles() {
            const input = document.getElementById('file-upload');
            if (input.files.length === 0) {
                alert('Please select files to upload');
                return;
            }

            // Implement file upload
            alert('File upload functionality to be implemented');
            closeUploadModal();
        }

        function confirmRename() {
            const newName = document.getElementById('rename-input').value.trim();
            if (!newName) {
                alert('Please enter a name');
                return;
            }

            if (window.fileExplorer?.contextItem) {
                fileExplorer.renameItem(fileExplorer.contextItem.name, newName);
                closeRenameModal();
            }
        }

        // Utility Functions
        function createNewFolder() {
            const folderName = prompt('Enter folder name:');
            if (folderName) {
                fileExplorer.createFolder(folderName);
            }
        }

        function refreshFolder() {
            if (window.fileExplorer) {
                fileExplorer.loadFolder(fileExplorer.currentPath);
            }
        }

        // Navigation Functions
        function goBack() {
            if (window.fileExplorer) {
                fileExplorer.goBack();
            }
        }

        function goForward() {
            if (window.fileExplorer) {
                fileExplorer.goForward();
            }
        }

        function goUp() {
            if (window.fileExplorer) {
                fileExplorer.goUp();
            }
        }

        // Initialize File Explorer
        let fileExplorer;

        document.addEventListener('DOMContentLoaded', () => {
            fileExplorer = new FileExplorer();
            window.fileExplorer = fileExplorer;
        });
    </script>