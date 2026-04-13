<?php
    $api_file_explorer = $ip_port . "api/travelers/file-explorer.php";
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
        max-width: 500px;
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
    
    .btn-success {
        background: #10b981;
        color: white;
    }
    
    .btn-success:hover {
        background: #059669;
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
    
    #context-menu {
        max-width: 250px;
        overflow: hidden;
        position: fixed;
        z-index: 10000;
    }
    
    #context-menu .context-menu-item {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .file-name {
        text-align: center;
        font-size: 15px;
        font-weight: 500;
        color: #374151;
        margin-top: 8px;
        word-break: break-word;
        overflow-wrap: break-word;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.3;
        min-height: 40px;
    }
    
    .list-view .file-item {
        display: flex;
        align-items: center;
        padding: 10px;
        margin-bottom: 5px;
    }
    
    .list-view .file-icon {
        width: 40px;
        text-align: center;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .list-view .file-name {
        text-align: left;
        flex: 1;
        margin-top: 0;
        min-height: auto;
        font-size: 14px;
    }
    
    .list-view .file-size {
        width: 80px;
        text-align: right;
        font-size: 13px;
        color: #6b7280;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .list-view .file-date {
        width: 150px;
        text-align: right;
        font-size: 13px;
        color: #6b7280;
        flex-shrink: 0;
    }
    
    .properties-panel {
        width: 320px;
        background: #f9fafb;
        border-left: 1px solid #e5e7eb;
        padding: 20px;
        overflow-y: auto;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .properties-panel.hidden {
        display: none;
    }
    
    .properties-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .properties-header h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }
    
    .close-panel {
        cursor: pointer;
        font-size: 20px;
        color: #6b7280;
        transition: color 0.2s;
    }
    
    .close-panel:hover {
        color: #ef4444;
    }
    
    .property-item {
        margin-bottom: 15px;
        padding: 10px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    
    .property-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    
    .property-value {
        font-size: 14px;
        color: #1f2937;
        word-break: break-word;
    }
    
    .property-value i {
        margin-right: 8px;
        color: #3b82f6;
    }
    
    .property-preview {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .property-preview .file-icon {
        font-size: 64px;
        margin-bottom: 10px;
    }
    
    .no-selection {
        text-align: center;
        padding: 40px 20px;
        color: #9ca3af;
    }
    
    .view-toggle {
        display: inline-flex;
        gap: 5px;
        margin-left: 10px;
    }
    
    .view-btn {
        padding: 6px 12px;
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .view-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .view-btn i {
        margin-right: 5px;
    }
    
    .show-panel-btn {
        position: fixed;
        right: 20px;
        bottom: 80px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 99;
    }
    
    .show-panel-btn:hover {
        background: #2563eb;
    }
    
    @media (max-width: 768px) {
        .properties-panel {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            z-index: 100;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        }
        
        .show-panel-btn {
            display: flex;
        }
    }
    
    .folder-tree {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 10px;
        background: #f9fafb;
    }
    
    .folder-tree-item {
        padding: 8px 10px;
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .folder-tree-item:hover {
        background-color: #f3f4f6;
    }
    
    .folder-tree-item.selected {
        background-color: #eff6ff;
        border-left: 3px solid #3b82f6;
    }
    
    .folder-tree-item .folder-name {
        display: flex;
        align-items: center;
        flex: 1;
    }
    
    .folder-tree-item .folder-name i {
        margin-right: 8px;
    }
    
    .folder-tree-item .new-folder-btn {
        opacity: 0;
        transition: opacity 0.2s;
        padding: 4px 8px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 11px;
    }
    
    .folder-tree-item:hover .new-folder-btn {
        opacity: 1;
    }
    
    .folder-tree-item .new-folder-btn:hover {
        background: #059669;
    }
    
    .new-folder-section {
        margin-top: 10px;
        padding: 10px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 6px;
        display: none;
    }
    
    .new-folder-section.active {
        display: block;
    }
    
    .new-folder-section input {
        width: 100%;
        padding: 8px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .new-folder-section .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        margin-right: 8px;
    }

    /* Data Entry Modal Styles */
    .data-entry-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .data-entry-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .data-entry-group label {
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .data-entry-group input,
    .data-entry-group textarea,
    .data-entry-group select {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s;
    }
    
    .data-entry-group input:focus,
    .data-entry-group textarea:focus,
    .data-entry-group select:focus {
        outline: none;
        border-color: #3b82f6;
        ring: 2px solid #3b82f6;
    }
    
    .data-entry-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .form-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 10px;
    }
    
    /* Document Type Badge Styles */
    .nid-badge {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 12px;
        position: absolute;
        top: 5px;
        right: 5px;
        z-index: 10;
    }
    
    .passport-badge {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 12px;
        position: absolute;
        top: 5px;
        right: 5px;
        z-index: 10;
    }
    
    .file-item.group {
        transition: all 0.2s ease;
        position: relative;
    }
    
    .file-item.group:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .data-entry-quick-btn {
        position: absolute;
        bottom: 5px;
        left: 50%;
        transform: translateX(-50%);
        background: #10b981;
        color: white;
        border-radius: 20px;
        padding: 4px 10px;
        font-size: 10px;
        font-weight: bold;
        opacity: 0;
        transition: opacity 0.2s;
        cursor: pointer;
        z-index: 20;
        white-space: nowrap;
    }
    
    .file-item.group:hover .data-entry-quick-btn {
        opacity: 1;
    }
    
    .data-entry-quick-btn:hover {
        background: #059669;
    }
    
    .pdf-badge {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: #ef4444;
        color: white;
        font-size: 9px;
        padding: 2px 5px;
        border-radius: 4px;
        font-weight: bold;
    }
</style>

<!-- Desktop with File Explorer -->
<div id="travelersFile" class="mt-2 flex-1 h-[36rem]">
    <div class="bg-white rounded-lg overflow-hidden flex flex-col h-full shadow-2xl border border-gray-200">
        <!-- Address Bar -->
        <div class="bg-gray-100 px-4 py-2 flex items-center gap-3 border-b border-gray-300 flex-wrap">
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
            
            <div class="flex-1 bg-gray-50 px-4 py-2 border-b border-gray-300 min-w-[200px]">
                <div class="flex items-center gap-2 text-sm text-gray-600 flex-wrap" id="breadcrumb"></div>
            </div>
            
            <div class="view-toggle">
                <button class="view-btn active" onclick="FileExplorer.setView('grid')" id="grid-view-btn">
                    <i class="fas fa-th"></i> Grid
                </button>
                <button class="view-btn" onclick="FileExplorer.setView('list')" id="list-view-btn">
                    <i class="fas fa-list"></i> List
                </button>
            </div>
            
            <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" onclick="FileExplorer.refresh()" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600" onclick="FileExplorer.createNewFolder()" title="New Folder">
                <i class="fas fa-plus"></i>
            </button>
            <button class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600" onclick="FileExplorer.showUploadModal()" title="Upload">
                <i class="fas fa-upload"></i>
            </button>
            <button class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" onclick="FileExplorer.togglePropertiesPanel()" title="Toggle Properties" id="toggle-panel-btn">
                <i class="fas fa-info-circle"></i>
            </button>
        </div>

        <!-- Main Content with Properties Panel -->
        <div class="flex flex-1 overflow-hidden">
            <div class="flex-1 p-5 overflow-y-auto" id="main-file-area">
                <div id="files-container" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    <div class="col-span-full text-center py-10">
                        <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mb-3"></i>
                        <p class="text-gray-600">Loading files...</p>
                    </div>
                </div>
            </div>
            
            <div class="properties-panel" id="properties-panel">
                <div class="properties-header">
                    <h3><i class="fas fa-info-circle"></i> Properties</h3>
                    <span class="close-panel" onclick="FileExplorer.closePropertiesPanel()">&times;</span>
                </div>
                <div id="properties-content">
                    <div class="no-selection">
                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                        <p>No file selected</p>
                        <p class="text-sm">Click on any file to view its properties</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-blue-500 text-white px-4 py-1 text-sm flex justify-between" id="status-bar">
            <div id="status-text">Loading...</div>
            <div id="folder-info"></div>
        </div>
    </div>
</div>

<button class="show-panel-btn" id="show-panel-btn" onclick="FileExplorer.showPropertiesPanel()" style="display: none;">
    <i class="fas fa-info-circle"></i>
</button>

<div id="toast" class="toast"></div>

<div id="context-menu" class="fixed bg-white border border-gray-300 rounded shadow-xl z-50 hidden">
    <div class="py-2 px-4 min-w-[180px]">
        <div class="context-menu-item" onclick="FileExplorer.contextOpen()">
            <i class="fas fa-folder-open w-5 mr-2 text-blue-500"></i> Open
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextEdit()">
            <i class="fas fa-edit w-5 mr-2 text-blue-500"></i> Edit
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
        <!-- Dynamic Data Entry Item -->
        <div id="data-entry-menu-item" class="context-menu-item" onclick="FileExplorer.contextDataEntry()" style="display: none;">
            <i class="fas fa-database w-5 mr-2 text-green-500"></i> Data Entry
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextDelete()">
            <i class="fas fa-trash-alt w-5 mr-2 text-red-500"></i> Delete
        </div>
        <div class="context-menu-item" onclick="FileExplorer.contextProperties()">
            <i class="fas fa-info-circle w-5 mr-2 text-blue-500"></i> Properties
        </div>
    </div>
</div>

<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="upload-modal">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4 text-gray-800">Upload Files to: <span id="current-upload-path" class="text-blue-600"></span></h3>
        <input type="file" id="file-upload" class="w-full p-2 border border-gray-300 rounded bg-gray-50 mb-4 text-gray-700" multiple>
        <div class="flex justify-end gap-3">
            <button class="btn btn-secondary" onclick="FileExplorer.closeUploadModal()">Cancel</button>
            <button class="btn btn-primary" onclick="FileExplorer.uploadFiles()">Upload</button>
        </div>
    </div>
</div>

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
    const travelerId = `<?php echo $travelerId; ?>`;
    const SERVER_NAME = `<?php echo $_SESSION['scp']; ?>`;
    const API_FILE_EXPLORER = `<?php echo $api_file_explorer; ?>`;

    const FileExplorer = {
        state: {
            currentPath: '',
            clientFolder: '',
            travelerFolder: '',
            history: [],
            historyIndex: -1,
            selectedItem: null,
            contextItem: null,
            clipboardData: null,
            currentFileForAction: null,
            downloadBlob: null,
            downloadFilename: null,
            clipboardAction: null,
            clipboardItem: null,
            clipboardSourcePath: '',
            clipboardSourceName: '',
            moveItem: null,
            moveSourcePath: '',
            moveSourceName: '',
            selectedMovePath: null,
            currentView: 'grid',
            allFolders: [],
            creatingFolderInPath: null,
            dataEntryItem: null
        },
        
        config: {
            apiBaseUrl: `${API_FILE_EXPLORER}`,
            baseStoragePath: `/storage/travelers/`,
            maxFileSizeForClipboard: 5 * 1024 * 1024
        },
        
        async init() {
            await this.loadFolder('');
            this.setupEventListeners();
            this.updateNavigationButtons();
            this.setView('grid');
        },
        
        async loadFolder(path = '') {
            try {
                this.showLoading(true);
                
                const response = await fetch(`${this.config.apiBaseUrl}?traveler_id=${travelerId}&action=list&path=${encodeURIComponent(path)}`);
                
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                
                const data = await response.json();
                
                if (data.success) {
                    this.state.currentPath = data.currentPath || data.path || '';
                    this.state.clientFolder = data.clientFolder || '';
                    this.state.travelerFolder = data.travelerFolder || '';
                    
                    this.addToHistory(this.state.currentPath);
                    
                    if (data.contents && Array.isArray(data.contents)) {
                        this.renderFiles(data.contents);
                    } else {
                        this.renderFiles([]);
                    }
                    
                    this.updateBreadcrumb();
                    this.updateStatusBar(data.contents?.length || 0);
                    
                    this.state.selectedItem = null;
                    this.showPropertiesInPanel(null);
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
        
        setView(view) {
            this.state.currentView = view;
            const container = document.getElementById('files-container');
            
            document.getElementById('grid-view-btn').classList.toggle('active', view === 'grid');
            document.getElementById('list-view-btn').classList.toggle('active', view === 'list');
            
            if (view === 'grid') {
                container.className = 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4';
            } else {
                container.className = 'list-view';
            }
            
            if (this.state.currentFiles) {
                this.renderFiles(this.state.currentFiles);
            }
        },
        
        togglePropertiesPanel() {
            const panel = document.getElementById('properties-panel');
            if (panel.classList.contains('hidden')) {
                this.showPropertiesPanel();
            } else {
                this.closePropertiesPanel();
            }
        },
        
        showPropertiesPanel() {
            const panel = document.getElementById('properties-panel');
            panel.classList.remove('hidden');
            const showBtn = document.getElementById('show-panel-btn');
            if (showBtn) showBtn.style.display = 'none';
        },
        
        closePropertiesPanel() {
            const panel = document.getElementById('properties-panel');
            panel.classList.add('hidden');
            const showBtn = document.getElementById('show-panel-btn');
            if (showBtn && window.innerWidth <= 768) {
                showBtn.style.display = 'flex';
            }
        },
        
        async createNewFolder() {
            const folderName = prompt('Enter folder name:');
            if (!folderName?.trim()) {
                this.showToast('Folder name cannot be empty', 'error');
                return;
            }

            try {
                const response = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
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
        
        // ========== DOCUMENT TYPE DETECTION METHODS ==========
        
        detectDocumentType(file) {
            const fileName = file.name.toLowerCase();
            const filePath = (file.path || '').toLowerCase();
            const currentDir = this.state.currentPath.toLowerCase();
            const fullPath = `${currentDir}/${file.name}`.toLowerCase();
            
            // Check for NID context (folder or filename)
            if (fullPath.includes('/nid/') || 
                fullPath.includes('/national_id/') ||
                currentDir.includes('/nid') ||
                filePath.includes('/nid') ||
                fileName.includes('nid') ||
                fileName.includes('national_id')) {
                return 'nid';
            }
            
            // Check for Passport context (folder or filename)
            if (fullPath.includes('/passports/') ||
                fullPath.includes('/ppt/') ||
                currentDir.includes('/passports') ||
                filePath.includes('/passports') ||
                fileName.includes('passports') ||
                fileName.includes('ppt')) {
                return 'passport';
            }
            
            // if (fullPath.includes('/passport/') ||
            //     fullPath.includes('/passports/') ||
            //     fullPath.includes('/ppt/') ||
            //     currentDir.includes('/passport') ||
            //     currentDir.includes('/passports') ||
            //     filePath.includes('/passport') ||
            //     filePath.includes('/passports') ||
            //     fileName.includes('passport') ||
            //     fileName.includes('passports') ||
            //     fileName.includes('ppt')) {
            //     return 'passport';
            // }
            
            return 'none';
        },
        
        shouldShowDataEntry(file) {
            // Check if file type is supported (images OR PDF)
            const supportedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'];
            const ext = file.name.split('.').pop().toLowerCase();
            const isSupported = supportedExts.includes(ext);
            
            if (!isSupported) return false;
            
            // Check if in NID or Passport context
            const docType = this.detectDocumentType(file);
            return (docType === 'nid' || docType === 'passport');
        },
        
        getDataEntryUrl(file) {
            const docType = this.detectDocumentType(file);
            
            // Get the relative path for the API
            const relativePath = this.getRelativePathForApi(file);
            
            if (docType === 'nid') {
                // Script is in pages folder
                return `pages/nid-data-entry.php?path=${encodeURIComponent(relativePath)}`;
            } else if (docType === 'passport') {
                return `pages/passport-data-entry.php?path=${encodeURIComponent(relativePath)}`;
            }
            return null;
        },
        
        getRelativePathForApi(file) {
            // Construct the path that the data entry scripts expect
            const baseStorage = `/storage/travelers/`;
            const fullPath = this.getFullFilePath(file);
            
            // Remove base storage path to get relative path
            let relativePath = fullPath.replace(baseStorage, '');
            
            // Ensure it starts with the correct format
            if (!relativePath.startsWith('/')) {
                relativePath = '/' + relativePath;
            }
            
            return relativePath;
        },
        
        renderFiles(files) {
            this.state.currentFiles = files;
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
            
            if (this.state.currentView === 'grid') {
                files.forEach(file => {
                    const fileElement = this.createGridFileElement(file);
                    container.appendChild(fileElement);
                });
            } else {
                const listHtml = this.createListFileElements(files);
                container.innerHTML = listHtml;
                
                document.querySelectorAll('.file-item').forEach((element, index) => {
                    const file = files[index];
                    element.addEventListener('click', (e) => this.handleFileClick(e, file, element));
                    element.addEventListener('dblclick', () => this.handleFileDoubleClick(file));
                    element.addEventListener('contextmenu', (e) => this.showContextMenu(e, file, element));
                });
            }
        },
        
        createGridFileElement(file) {
            const div = document.createElement('div');
            div.className = 'file-item cursor-pointer p-3 rounded-lg hover:bg-gray-50 border border-transparent hover:border-gray-200 transition-colors text-center relative group';
            div.dataset.name = file.name;
            div.dataset.type = file.type;
            div.dataset.path = file.path;
            
            const icon = this.getFileIcon(file);
            const docType = this.detectDocumentType(file);
            const showDataEntry = this.shouldShowDataEntry(file);
            
            // Add document type badge
            let badgeHtml = '';
            if (showDataEntry) {
                if (docType === 'nid') {
                    badgeHtml = '<span class="nid-badge" title="NID Document">🪪 NID</span>';
                } else if (docType === 'passport') {
                    badgeHtml = '<span class="passport-badge" title="Passport Document">📘 Passport</span>';
                }
            }
            
            // Add PDF badge
            const ext = file.name.split('.').pop().toLowerCase();
            const isPdf = ext === 'pdf';
            const pdfBadge = isPdf ? '<span class="pdf-badge">PDF</span>' : '';
            
            div.innerHTML = `
                ${badgeHtml}
                ${pdfBadge}
                <div class="flex justify-center mb-2">
                    <div class="text-4xl ${icon.color}">
                        <i class="${icon.class}"></i>
                    </div>
                </div>
                <div class="file-name" title="${this.escapeHtml(file.name)}">
                    ${this.escapeHtml(file.name)}
                </div>
                <div class="text-xs text-gray-500 mt-1 text-center">${file.size}</div>
            `;
            
            // Add quick action button for data entry
            if (showDataEntry) {
                const actionBtn = document.createElement('button');
                actionBtn.className = 'data-entry-quick-btn';
                actionBtn.innerHTML = docType === 'nid' ? '🪪 NID Data Entry' : '📘 Passport Data Entry';
                actionBtn.title = `Open ${docType.toUpperCase()} Data Entry`;
                actionBtn.onclick = (e) => {
                    e.stopPropagation();
                    const url = this.getDataEntryUrl(file);
                    if (url) window.open(url, '_blank');
                };
                div.appendChild(actionBtn);
            }
            
            div.addEventListener('click', (e) => this.handleFileClick(e, file, div));
            div.addEventListener('dblclick', () => this.handleFileDoubleClick(file));
            div.addEventListener('contextmenu', (e) => this.showContextMenu(e, file, div));
            
            return div;
        },
        
        createListFileElements(files) {
            let html = '<div class="flex flex-col">';
            
            files.forEach(file => {
                const icon = this.getFileIcon(file);
                const lastModified = file.lastModified || 'N/A';
                const size = file.size || 'N/A';
                const showDataEntry = this.shouldShowDataEntry(file);
                const docType = this.detectDocumentType(file);
                
                let dataEntryBadge = '';
                if (showDataEntry) {
                    dataEntryBadge = `<span class="ml-2 px-2 py-0.5 text-xs rounded-full ${docType === 'nid' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'}">
                        ${docType === 'nid' ? '🪪 NID' : '📘 Passport'}
                    </span>`;
                }
                
                // Add PDF indicator
                const ext = file.name.split('.').pop().toLowerCase();
                const pdfIndicator = ext === 'pdf' ? '<span class="ml-1 text-xs text-red-500">📄</span>' : '';
                
                html += `
                    <div class="file-item cursor-pointer flex items-center p-3 hover:bg-gray-50 border-b border-gray-200 transition-colors" 
                         data-name="${this.escapeHtml(file.name)}" data-type="${file.type}" data-path="${file.path}">
                        <div class="file-icon text-2xl ${icon.color} mr-4">
                            <i class="${icon.class}"></i>
                        </div>
                        <div class="file-name flex-1 flex items-center">
                            ${this.escapeHtml(file.name)}
                            ${pdfIndicator}
                            ${dataEntryBadge}
                        </div>
                        <div class="file-size">
                            ${size}
                        </div>
                        <div class="file-date">
                            ${lastModified}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            return html;
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
            
            this.showPropertiesInPanel(file);
        },
        
        showPropertiesInPanel(file) {
            const panel = document.getElementById('properties-content');
            
            if (!file) {
                panel.innerHTML = `
                    <div class="no-selection">
                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                        <p>No file selected</p>
                        <p class="text-sm">Click on any file to view its properties</p>
                    </div>
                `;
                return;
            }
            
            const icon = this.getFileIcon(file);
            const fileExtension = file.type === 'folder' ? 'Folder' : file.name.split('.').pop().toUpperCase();
            const lastModified = file.lastModified || new Date().toLocaleString();
            const size = file.size || (file.type === 'folder' ? '—' : 'Unknown');
            const docType = this.detectDocumentType(file);
            
            let previewHtml = '';
            
            if (file.type !== 'folder') {
                const ext = file.name.split('.').pop().toLowerCase();
                const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
                
                if (imageExts.includes(ext)) {
                    const imageUrl = this.getFullFilePath(file);
                    previewHtml = `
                        <div class="property-preview">
                            <img src="${imageUrl}" alt="${this.escapeHtml(file.name)}" style="max-width: 100%; max-height: 150px; border-radius: 8px;">
                        </div>
                    `;
                } else if (ext === 'pdf') {
                    previewHtml = `
                        <div class="property-preview">
                            <i class="fas fa-file-pdf text-red-500" style="font-size: 64px;"></i>
                            <p class="text-sm text-gray-600 mt-2">PDF Document</p>
                        </div>
                    `;
                } else {
                    previewHtml = `
                        <div class="property-preview">
                            <i class="${icon.class} ${icon.color}" style="font-size: 64px;"></i>
                        </div>
                    `;
                }
            } else {
                previewHtml = `
                    <div class="property-preview">
                        <i class="${icon.class} ${icon.color}" style="font-size: 64px;"></i>
                        <p class="text-sm text-gray-600 mt-2">Folder</p>
                    </div>
                `;
            }
            
            let docTypeInfo = '';
            if (docType !== 'none') {
                docTypeInfo = `
                    <div class="property-item">
                        <div class="property-label">Document Type</div>
                        <div class="property-value">
                            ${docType === 'nid' ? '🪪 National ID (NID)' : '📘 Passport'}
                        </div>
                    </div>
                `;
            }
            
            panel.innerHTML = `
                ${previewHtml}
                ${docTypeInfo}
                <div class="property-item">
                    <div class="property-label">Name</div>
                    <div class="property-value">${this.escapeHtml(file.name)}</div>
                </div>
                <div class="property-item">
                    <div class="property-label">Type</div>
                    <div class="property-value">${file.type === 'folder' ? 'Folder' : fileExtension}</div>
                </div>
                <div class="property-item">
                    <div class="property-label">Size</div>
                    <div class="property-value">${size}</div>
                </div>
                <div class="property-item">
                    <div class="property-label">Last Modified</div>
                    <div class="property-value">${lastModified}</div>
                </div>
                <div class="property-item">
                    <div class="property-label">Path</div>
                    <div class="property-value">${this.escapeHtml(file.path)}</div>
                </div>
            `;
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
            const dataEntryMenuItem = document.getElementById('data-entry-menu-item');
            
            // Show/hide and customize Data Entry based on file type and location
            if (this.shouldShowDataEntry(file)) {
                const docType = this.detectDocumentType(file);
                dataEntryMenuItem.style.display = 'flex';
                const icon = dataEntryMenuItem.querySelector('i');
                if (docType === 'nid') {
                    icon.className = 'fas fa-id-card w-5 mr-2 text-green-500';
                    dataEntryMenuItem.innerHTML = '<i class="fas fa-id-card w-5 mr-2 text-green-500"></i> NID Data Entry';
                } else if (docType === 'passport') {
                    icon.className = 'fas fa-passport w-5 mr-2 text-blue-500';
                    dataEntryMenuItem.innerHTML = '<i class="fas fa-passport w-5 mr-2 text-blue-500"></i> Passport Data Entry';
                }
            } else {
                dataEntryMenuItem.style.display = 'none';
            }
            
            // Calculate position to prevent going off-screen
            let left = e.pageX;
            let top = e.pageY;
            
            const menuWidth = menu.offsetWidth || 200;
            const menuHeight = menu.offsetHeight || 300;
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;
            
            // Adjust if menu goes off-screen to the right
            if (left + menuWidth > windowWidth) {
                left = windowWidth - menuWidth - 10;
            }
            
            // Adjust if menu goes off-screen to the bottom
            if (top + menuHeight > windowHeight) {
                top = windowHeight - menuHeight - 10;
            }
            
            // Ensure it doesn't go off-screen to the left or top
            left = Math.max(10, left);
            top = Math.max(10, top);
            
            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
            menu.classList.remove('hidden');
            
            this.state.contextItem = file;
            this.state.currentFileForAction = file;
        },
        
        getFullFilePath(file) {
            const cleanPath = file.path.replace(/\\/g, '/');
            return `${this.config.baseStoragePath}${this.state.clientFolder ? this.state.clientFolder + '/' : ''}${this.state.travelerFolder}/${cleanPath}`;
        },
        
        contextDataEntry() {
            if (!this.state.contextItem) return;
            
            const url = this.getDataEntryUrl(this.state.contextItem);
            if (url) {
                // Open in new tab
                window.open(url, '_blank');
                const docType = this.detectDocumentType(this.state.contextItem);
                this.showToast(`Opening ${docType.toUpperCase()} Data Entry...`, 'info');
            } else {
                this.showToast('Data entry not available for this file', 'error');
            }
            
            this.hideContextMenu();
        },
        
        async contextCopyFileToClipboard() {
            if (!this.state.contextItem) return;
            
            const file = this.state.contextItem;
            
            if (file.type === 'folder') {
                this.showToast('Cannot copy folder to clipboard', 'error');
                this.hideContextMenu();
                return;
            }
            
            await this.smartCopyFileToClipboard();
            this.hideContextMenu();
        },
        
        async smartCopyFileToClipboard() {
            const file = this.state.contextItem;
            if (!file) return;
            
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(fileExtension);
            const isDocument = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'].includes(fileExtension);
        
            if (isImage) {
                await this.copyImageToClipboard(file);
            } else if (isDocument) {
                const fileUrl = this.getFullFilePath(file);
                const absoluteUrl = window.location.origin + fileUrl;
                
                try {
                    await navigator.clipboard.writeText(absoluteUrl);
                    this.showToast('File link copied to clipboard!', 'success');
                    this.hideContextMenu();
                } catch (err) {
                    console.error('Failed to copy link:', err);
                    this.showToast('Failed to copy link', 'error');
                }
            } else {
                const fileUrl = this.getFullFilePath(file);
                const absoluteUrl = window.location.origin + fileUrl;
                await navigator.clipboard.writeText(absoluteUrl);
                this.showToast('Link copied to clipboard', 'success');
            }
        },
        
        async copyImageToClipboard(file) {
            this.showClipboardModal('Loading image...');
            
            try {
                const fileUrl = this.getFullFilePath(file);
                const response = await fetch(fileUrl);
                
                if (!response.ok) {
                    throw new Error(`Failed to fetch image: ${response.status}`);
                }
                
                const blob = await response.blob();
                
                if (blob.size > this.config.maxFileSizeForClipboard) {
                    this.showToast(`Image too large (${this.formatFileSize(blob.size)}). Maximum ${this.formatFileSize(this.config.maxFileSizeForClipboard)} allowed.`, 'error');
                    this.closeClipboardModal();
                    return;
                }
                
                this.updateClipboardStatus('Copying image to clipboard...');
                
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
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
        
        contextOpen() {
            if (!this.state.contextItem) return;
            
            if (this.state.contextItem.type === 'folder') {
                this.navigateToFolder(this.state.contextItem.name);
            } else {
                window.open(this.getFullFilePath(this.state.contextItem), '_blank');
            }
            
            this.hideContextMenu();
        },
        
        contextEdit() {
            if (!this.state.contextItem) return;
            
            if (this.state.contextItem.type === 'folder') {
                this.showToast('Cannot edit a folder', 'error');
                return;
            }
            
            // Check if it's an image file
            const ext = this.state.contextItem.name.split('.').pop().toLowerCase();
            const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            
            if (imageExts.includes(ext)) {
                // Get the SMB path for the image
                const smbPath = this.getSmbPathForEditor(this.state.contextItem);
                
                // Open the image editor with the SMB path
                const editorUrl = `img-editor.php?img=${encodeURIComponent(smbPath)}`;
                window.open(editorUrl, '_blank');
                
                this.showToast('Opening image editor...', 'info');
            } 
            // For text files
            else if (['txt', 'md', 'json', 'xml', 'csv'].includes(ext)) {
                // You can implement text file editing here if needed
                this.showToast('Text file editing coming soon', 'info');
            } 
            // For PDF and other unsupported files
            else {
                this.showToast('This file type cannot be edited', 'error');
            }
            
            this.hideContextMenu();
        },
        
        // Add this helper method to get the correct SMB path format for the editor
        getSmbPathForEditor(file) {
            // The format expected by img-editor.php is: rnd_traveler/{travelerFolder}/{path}/{filename}
            // Example: "rnd_traveler/niloy_5c0e526c/all_documents/photo.jpg"
            
            // Get the relative path from the file object
            let relativePath = file.path || '';
            
            // Remove any leading/trailing slashes
            relativePath = relativePath.replace(/^\/+|\/+$/g, '');
            
            // Construct the full SMB path
            const smbPath = `rnd_traveler/${this.state.travelerFolder}/${relativePath}`;
            
            // Remove any double slashes that might have been created
            return smbPath.replace(/\/+/g, '/');
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
                if (this.state.currentPath === this.state.clipboardSourcePath) {
                    this.showToast('Cannot paste in the same folder for cut operation', 'error');
                    return;
                }
                
                try {
                    const response = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
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
                        
                        this.state.clipboardAction = null;
                        this.state.clipboardItem = null;
                        this.state.clipboardSourcePath = '';
                        this.state.clipboardSourceName = '';
                        
                        await this.refresh();
                    } else {
                        this.showToast(data.error || 'Failed to move item', 'error');
                    }
                } catch (error) {
                    this.showToast('Failed to move item', 'error');
                }
            } else if (action === 'copy') {
                try {
                    const response = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
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
            
            await this.loadFoldersForMove();
            
            this.hideContextMenu();
        },
        
        async loadFoldersForMove() {
            try {
                const response = await fetch(`${this.config.apiBaseUrl}?traveler_id=${travelerId}&action=list_folders`);
                const data = await response.json();
                
                if (data.success && data.folders) {
                    this.state.allFolders = data.folders;
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
            const existingModal = document.getElementById('move-modal');
            if (existingModal) {
                existingModal.remove();
            }
            
            let folderTreeHtml = '<div class="folder-tree">';
            
            folderTreeHtml += `
                <div class="folder-tree-item" data-path="" onclick="FileExplorer.selectMoveFolder('')">
                    <div class="folder-name">
                        <i class="fas fa-folder text-yellow-500"></i>
                        <span>Root (/)</span>
                    </div>
                    <button class="new-folder-btn" onclick="event.stopPropagation(); FileExplorer.showCreateFolderInMove('')">
                        <i class="fas fa-plus"></i> New
                    </button>
                </div>
            `;
            
            const sortedFolders = [...folders].sort((a, b) => a.path.localeCompare(b.path));
            
            sortedFolders.forEach(folder => {
                if (folder.path === '') return;
                const indent = (folder.path.split('/').length - 1) * 20;
                folderTreeHtml += `
                    <div class="folder-tree-item" data-path="${this.escapeHtml(folder.path)}" style="padding-left: ${indent + 20}px" onclick="FileExplorer.selectMoveFolder('${folder.path.replace(/'/g, "\\'")}')">
                        <div class="folder-name">
                            <i class="fas fa-folder text-yellow-500"></i>
                            <span>${this.escapeHtml(folder.name)}</span>
                            <span class="text-xs text-gray-400 ml-2">${this.escapeHtml(folder.path)}</span>
                        </div>
                        <button class="new-folder-btn" onclick="event.stopPropagation(); FileExplorer.showCreateFolderInMove('${folder.path.replace(/'/g, "\\'")}')">
                            <i class="fas fa-plus"></i> New
                        </button>
                    </div>
                `;
            });
            
            folderTreeHtml += '</div>';
            
            folderTreeHtml += `
                <div id="new-folder-section" class="new-folder-section">
                    <input type="text" id="new-folder-name" placeholder="Enter folder name..." autocomplete="off">
                    <div>
                        <button class="btn btn-success btn-sm" onclick="FileExplorer.createFolderInMove()">
                            <i class="fas fa-check"></i> Create
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="FileExplorer.hideCreateFolderInMove()">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            
            const modalContent = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Move Item</h3>
                        <span class="close-btn" onclick="FileExplorer.closeMoveModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <p><strong>Moving:</strong> <span class="text-blue-600">${this.escapeHtml(this.state.moveItem.name)}</span></p>
                            <p class="text-sm text-gray-600"><strong>From:</strong> ${this.state.moveSourcePath || '/'}</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select destination folder:</label>
                            ${folderTreeHtml}
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">New name (optional):</label>
                            <input type="text" id="moveNewName" class="w-full p-2 border border-gray-300 rounded" 
                                   value="${this.escapeHtml(this.state.moveItem.name)}" placeholder="Enter new name">
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
        
        showCreateFolderInMove(parentPath) {
            this.state.creatingFolderInPath = parentPath || '';
            const section = document.getElementById('new-folder-section');
            const input = document.getElementById('new-folder-name');
            if (section && input) {
                section.classList.add('active');
                input.value = '';
                input.focus();
            }
        },
        
        hideCreateFolderInMove() {
            this.state.creatingFolderInPath = null;
            const section = document.getElementById('new-folder-section');
            if (section) {
                section.classList.remove('active');
            }
        },
        
        async createFolderInMove() {
            const folderName = document.getElementById('new-folder-name')?.value.trim();
            if (!folderName) {
                this.showToast('Please enter a folder name', 'error');
                return;
            }
            
            let parentPath = this.state.creatingFolderInPath || '';
            
            try {
                const response = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_folder',
                        path: parentPath,
                        name: folderName
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showToast(`Folder "${folderName}" created successfully`, 'success');
                    this.hideCreateFolderInMove();
                    // Reload folders to show the new folder in the move modal
                    await this.loadFoldersForMove();
                    
                    // Auto-select the newly created folder
                    const newFolderPath = parentPath ? `${parentPath}/${folderName}` : folderName;
                    this.selectMoveFolder(newFolderPath);
                } else {
                    this.showToast(data.error || 'Failed to create folder', 'error');
                }
            } catch (error) {
                console.error('Error creating folder:', error);
                this.showToast('Failed to create folder', 'error');
            }
        },
        
        closeMoveModal() {
            const modal = document.getElementById('move-modal');
            if (modal) {
                modal.style.display = 'none';
                modal.remove();
            }
            this.hideCreateFolderInMove();
        },
        
        selectMoveFolder(path) {
            this.state.selectedMovePath = path || '';
            const items = document.querySelectorAll('#move-modal .folder-tree-item');
            items.forEach(item => {
                item.classList.remove('selected');
                if (item.getAttribute('data-path') === path) {
                    item.classList.add('selected');
                }
            });
        },
        
        async confirmMove() {
            const newNameInput = document.getElementById('moveNewName');
            const newName = newNameInput ? newNameInput.value.trim() : this.state.moveItem.name;
            
            if (this.state.selectedMovePath === undefined) {
                this.showToast('Please select a destination folder', 'error');
                return;
            }
            
            try {
                const response = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'move',
                        sourcePath: this.state.moveSourcePath,
                        sourceName: this.state.moveSourceName,
                        targetPath: this.state.selectedMovePath || '',
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
            
            // Extension alada korar logic
            const lastDotIndex = itemName.lastIndexOf('.');
            let defaultNewName;
        
            if (lastDotIndex !== -1 && lastDotIndex !== 0) {
                // Jodi extension thake
                const namePart = itemName.substring(0, lastDotIndex);
                const extension = itemName.substring(lastDotIndex);
                defaultNewName = `${namePart} - Copy${extension}`;
            } else {
                // Jodi extension na thake (folder ba no extension file)
                defaultNewName = `${itemName} - Copy`;
            }
        
            const newName = prompt(`Enter new name for duplicate of "${itemName}":`, defaultNewName);
        
            if (!newName) return;
            
            try {
                const response = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
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
            
            if (newName === this.state.contextItem.name) {
                this.showToast('New name is same as old name', 'error');
                return;
            }
            
            try {
                const response = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}&action=rename`, {
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
                
                const data = await response.json();
                
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
                const response = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
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
            
            const docType = this.detectDocumentType(this.state.contextItem);
            let docTypeHtml = '';
            if (docType !== 'none') {
                docTypeHtml = `<div><strong class="text-gray-700">Document Type:</strong> ${docType === 'nid' ? '🪪 National ID (NID)' : '📘 Passport'}</div>`;
            }
            
            body.innerHTML = `
                <div class="space-y-3">
                    <div><strong class="text-gray-700">Name:</strong> <span class="text-wrap">${this.escapeHtml(this.state.contextItem.name)}</span></div>
                    ${docTypeHtml}
                    <div><strong class="text-gray-700">Type:</strong> ${this.state.contextItem.type}</div>
                    <div><strong class="text-gray-700">Size:</strong> ${this.state.contextItem.size || 'N/A'}</div>
                    <div><strong class="text-gray-700">Modified:</strong> ${this.state.contextItem.lastModified || 'N/A'}</div>
                    <div><strong class="text-gray-700">Path:</strong> <span class="text-xs text-wrap">${this.escapeHtml(this.state.contextItem.path)}</span></div>
                </div>
            `;
            
            modal.style.display = 'flex';
            this.hideContextMenu();
        },
        
        updateBreadcrumb() {
            const breadcrumb = document.getElementById('breadcrumb');
            const parts = this.state.currentPath.split('/').filter(p => p);
            
            let html = `
                <span class="text-blue-600 cursor-pointer hover:underline truncate-text" onclick="FileExplorer.loadFolder('')">
                    <i class="fas fa-home mr-1"></i>${this.state.travelerFolder || 'Traveler'}
                </span>
            `;
            
            let currentPath = '';
            parts.forEach((part, index) => {
                currentPath += (currentPath ? '/' : '') + part;
                const isLast = index === parts.length - 1;
                
                html += `<i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>`;
                
                if (isLast) {
                    html += `<span class="text-gray-800 font-medium truncate-text">${this.escapeHtml(part)}</span>`;
                } else {
                    html += `
                        <span class="text-blue-600 cursor-pointer hover:underline truncate-text" 
                              onclick="FileExplorer.loadFolder('${currentPath}')">
                            ${this.escapeHtml(part)}
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
                const currentFolder = parts.length > 0 ? parts[parts.length - 1] : this.state.travelerFolder;
                folderInfo.textContent = currentFolder || 'Traveler Folder';
            } else {
                folderInfo.textContent = this.state.travelerFolder || 'Traveler Folder';
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
            if (show && (!this.state.currentFiles || this.state.currentFiles.length === 0)) {
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
        
        showUploadModal() {
            document.getElementById('current-upload-path').textContent = this.state.currentPath || 'Root';
            document.getElementById('upload-modal').classList.remove('hidden');
        },
        
        closeUploadModal() {
            document.getElementById('upload-modal').classList.add('hidden');
            document.getElementById('file-upload').value = '';
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
            
            this.showToast('Uploading files...', 'info');
            
            try {
                const response = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
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
                console.error('Upload error:', error);
                this.showToast('Failed to upload files', 'error');
            }
        },
        
        setupEventListeners() {
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#context-menu')) {
                    this.hideContextMenu();
                }
            });
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.hideContextMenu();
                    this.closeUploadModal();
                    this.closeRenameModal();
                    this.closeModal();
                    this.closeClipboardModal();
                    this.closeMoveModal();
                }
                
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
            
            window.addEventListener('resize', () => {
                const panel = document.getElementById('properties-panel');
                const showBtn = document.getElementById('show-panel-btn');
                if (window.innerWidth <= 768 && panel.classList.contains('hidden')) {
                    showBtn.style.display = 'flex';
                } else if (window.innerWidth > 768) {
                    showBtn.style.display = 'none';
                }
            });
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && document.getElementById('new-folder-section')?.classList.contains('active')) {
                    e.preventDefault();
                    this.createFolderInMove();
                }
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        FileExplorer.init();
    });
</script>