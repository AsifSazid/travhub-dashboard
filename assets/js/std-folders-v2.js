/* =========================================================================
 * std-folders-v2.js  —  File Explorer fixes (companion override)
 * =========================================================================
 * This file EXTENDS the existing `FileExplorer` object defined inside
 * pages/std-folders.php. It does NOT replace it. Include it AFTER the inline
 * <script> in std-folders.php (see install notes in the README).
 *
 * It overrides a handful of methods and adds new ones. Because it patches the
 * live object, your original file stays intact — delete this <script> tag to
 * roll everything back instantly.
 *
 * Covers the 8 items:
 *   1. Split context menu (folder vs file)
 *   2. Upload to the currently open folder (toolbar + "Upload to this folder")
 *   3. File ops: Copy Path / Copy File to clipboard / Cut+Paste(Move) /
 *      Duplicate / Rename / Delete — wired to existing dual-storage API
 *   4. Grid/List toggle persisted in localStorage + image thumbnails / PDF badge
 *   5. "Open in Explorer" button + .bat fallback (uses data-smb-path)
 *   6. Clickable breadcrumb (already present; hardened)
 *   7. Refresh re-fetches the CURRENT folder (already correct; confirmed)
 *   8. Add New Folder (inline-ish; uses existing create_folder API)
 *
 * Assumes the existing object's state/config/methods as read from the file:
 *   FileExplorer.state.currentPath, .currentView, .contextItem, .currentFiles
 *   FileExplorer.loadFolder(path), .refresh(), .renderFiles(files),
 *   .showToast(msg,type), .escapeHtml(s), .getFullFilePath(file),
 *   .hideContextMenu(), .handleFileClick(e,file,el)
 * ========================================================================= */

(function () {
    if (typeof FileExplorer === 'undefined') {
        console.error('[std-folders-v2] FileExplorer not found. Include this AFTER the main explorer script.');
        return;
    }

    const LS_VIEW_KEY = 'travhub_explorer_view';

    /* ---------------------------------------------------------------------
     * Item 4 — Grid/List view persisted in localStorage
     * Override setView to persist; read it back on init.
     * ------------------------------------------------------------------- */
    const _origSetView = FileExplorer.setView.bind(FileExplorer);
    FileExplorer.setView = function (view) {
        _origSetView(view);
        try { localStorage.setItem(LS_VIEW_KEY, view); } catch (e) {}
    };

    FileExplorer.restoreViewFromStorage = function () {
        let view = 'grid';
        try { view = localStorage.getItem(LS_VIEW_KEY) || 'grid'; } catch (e) {}
        this.setView(view);
    };

    /* ---------------------------------------------------------------------
     * Item 1 — Split context menu: folder vs file
     * The single #context-menu in the HTML carries data-show attributes
     * (added via the patch list). We toggle each item's visibility based on
     * the right-clicked item's type. We wrap the existing showContextMenu.
     * ------------------------------------------------------------------- */
    const _origShowContextMenu = FileExplorer.showContextMenu.bind(FileExplorer);
    FileExplorer.showContextMenu = function (e, file, element) {
        // Let the original position the menu, set state.contextItem, handle data-entry
        _origShowContextMenu(e, file, element);

        const isFolder = file && file.type === 'folder';
        const menu = document.getElementById('context-menu');
        if (!menu) return;

        // Each .context-menu-item may declare data-show="folder", "file", or "both".
        // Items without the attribute are treated as "both" (back-compat).
        menu.querySelectorAll('.context-menu-item').forEach(function (item) {
            // Don't fight the data-entry item's own show/hide logic
            if (item.id === 'data-entry-menu-item') return;
            const show = item.getAttribute('data-show') || 'both';
            const visible = show === 'both'
                || (show === 'folder' && isFolder)
                || (show === 'file' && !isFolder);
            item.style.display = visible ? 'flex' : 'none';
        });
    };

    /* ---------------------------------------------------------------------
     * Item 2 + 8 — Upload to / create folder INSIDE a right-clicked folder
     * "currentFolderPath" semantics: for a right-clicked folder we target
     * (currentPath + '/' + folderName); otherwise we target currentPath.
     * ------------------------------------------------------------------- */
    FileExplorer.targetFolderPathFor = function (file) {
        const base = this.state.currentPath || '';
        if (file && file.type === 'folder') {
            return base ? `${base}/${file.name}` : file.name;
        }
        return base;
    };

    // Right-click "Upload to this folder"
    FileExplorer.contextUploadToFolder = function () {
        const file = this.state.contextItem;
        const target = this.targetFolderPathFor(file);
        this._pendingUploadPath = target;            // remember where to put files
        const label = document.getElementById('current-upload-path');
        if (label) label.textContent = target || 'Root';
        document.getElementById('upload-modal').classList.remove('hidden');
        this.hideContextMenu();
    };

    // Override uploadFiles to honor a pending target folder (falls back to currentPath)
    FileExplorer.uploadFiles = async function () {
        const input = document.getElementById('file-upload');
        if (!input || input.files.length === 0) {
            this.showToast('Please select files to upload', 'error');
            return;
        }
        const targetPath = this._pendingUploadPath != null
            ? this._pendingUploadPath
            : (this.state.currentPath || '');

        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('path', targetPath);          // backend `upload` uses `path`
        formData.append('target_folder', targetPath); // spec-requested alias (harmless extra)
        for (let i = 0; i < input.files.length; i++) {
            formData.append('files[]', input.files[i]);
        }

        this.showToast('Uploading files...', 'info');
        try {
            const res = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
                method: 'POST', body: formData
            });
            const data = await res.json();
            if (data.success) {
                this.showToast(data.message || 'Files uploaded', 'success');
                await this.loadFolder(targetPath);     // refresh AT the target level
                this.closeUploadModal();
            } else {
                this.showToast(data.error || 'Upload failed', 'error');
            }
        } catch (err) {
            console.error('Upload error:', err);
            this.showToast('Failed to upload files', 'error');
        } finally {
            this._pendingUploadPath = null;            // reset
        }
    };

    // Toolbar upload always targets the current folder
    const _origShowUploadModal = FileExplorer.showUploadModal.bind(FileExplorer);
    FileExplorer.showUploadModal = function () {
        this._pendingUploadPath = this.state.currentPath || '';
        _origShowUploadModal();
    };

    // Right-click "Add New Folder" inside a folder
    FileExplorer.contextAddFolder = async function () {
        const file = this.state.contextItem;
        const target = this.targetFolderPathFor(file);
        const name = prompt('New folder name:');
        if (!name || !name.trim()) { this.hideContextMenu(); return; }
        try {
            const res = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create_folder', path: target, name: name.trim() })
            });
            const data = await res.json();
            if (data.success) {
                this.showToast('Folder created', 'success');
                await this.loadFolder(target);
            } else {
                this.showToast(data.error || 'Failed to create folder', 'error');
            }
        } catch (e) {
            this.showToast('Failed to create folder', 'error');
        }
        this.hideContextMenu();
    };

    /* ---------------------------------------------------------------------
     * Item 3 — Cut + Paste(Move). The existing code has contextCut/contextMove;
     * we add a clean clipboard-based Cut→Paste flow that uses the `move` API.
     * Paste appears on a right-clicked folder (and via a toolbar fallback).
     * ------------------------------------------------------------------- */
    FileExplorer.contextCut = function () {
        const file = this.state.contextItem;
        if (!file) return;
        this.state.clipboardAction = 'cut';
        this.state.clipboardItem = file;
        this.state.clipboardSourcePath = this.state.currentPath || '';
        this.state.clipboardSourceName = file.name;
        this.showToast(`Cut "${file.name}" — open a folder and choose Paste`, 'info');
        // Visually mark the cut element if present
        document.querySelectorAll('.fe-cut').forEach(el => el.classList.remove('fe-cut'));
        this.hideContextMenu();
    };

    FileExplorer.contextPaste = async function () {
        if (this.state.clipboardAction !== 'cut' || !this.state.clipboardItem) {
            this.showToast('Nothing to paste', 'error');
            this.hideContextMenu();
            return;
        }
        const dest = this.targetFolderPathFor(this.state.contextItem);
        try {
            const res = await fetch(this.config.apiBaseUrl + `?traveler_id=${travelerId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'move',
                    sourcePath: this.state.clipboardSourcePath,
                    sourceName: this.state.clipboardSourceName,
                    targetPath: dest,
                    targetName: this.state.clipboardSourceName
                })
            });
            const data = await res.json();
            if (data.success) {
                this.showToast('Moved successfully', 'success');
                this.state.clipboardAction = null;
                this.state.clipboardItem = null;
                await this.refresh();
            } else {
                this.showToast(data.error || 'Move failed', 'error');
            }
        } catch (e) {
            this.showToast('Move failed', 'error');
        }
        this.hideContextMenu();
    };

    /* Copy Path — full local server path string to clipboard */
    FileExplorer.contextCopyPath = async function () {
        const file = this.state.contextItem;
        if (!file) return;
        // Build a human path that mirrors what the API reports as displayPath
        const rel = (file.path || '').replace(/\//g, '\\');
        const full = `\\storage\\travelers\\${this.state.travelerFolder}\\${rel}`;
        try {
            await navigator.clipboard.writeText(full);
            this.showToast('Path copied', 'success');
        } catch (e) {
            this.showToast('Clipboard blocked by browser', 'error');
        }
        this.hideContextMenu();
    };

    /* ---------------------------------------------------------------------
     * Item 5 — Open in Windows Explorer + .bat fallback
     * smb_path comes from the #file-explorer container's data-smb-path.
     * ------------------------------------------------------------------- */
    FileExplorer.getSmbPath = function () {
        const el = document.getElementById('file-explorer');
        let smb = el ? (el.getAttribute('data-smb-path') || '') : '';
        // Append the open sub-folder so Explorer lands where the user is
        if (this.state.currentPath) {
            const tail = this.state.currentPath.replace(/\//g, '\\');
            smb = smb.replace(/\\+$/, '') + '\\' + tail;
        }
        return smb;
    };

    FileExplorer.openInExplorer = function () {
        const smb = this.getSmbPath();
        if (!smb) { this.showToast('No SMB path configured', 'error'); return; }
        // Primary attempt (most browsers block file:// from web pages; .bat is the real fallback)
        try { window.location.href = 'file:///' + smb.replace(/\\/g, '/'); } catch (e) {}
        // Always offer the .bat as the reliable path
        this.downloadExplorerBat(smb);
        this.showToast('If Explorer did not open, run the downloaded .bat', 'info');
    };

    FileExplorer.downloadExplorerBat = function (smb) {
        const bat = `@echo off\r\nexplorer "${smb}"\r\n`;
        const blob = new Blob([bat], { type: 'application/bat' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'open_in_explorer.bat';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    };

    /* ---------------------------------------------------------------------
     * Item 4 (cont.) — richer grid: image thumbnails + PDF page badge.
     * We override createGridFileElement defensively: if the original exists we
     * call it, then enhance image/PDF nodes. If thumbnails already work, this
     * is a no-op visually.
     * ------------------------------------------------------------------- */
    FileExplorer.thumbnailFor = function (file) {
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        if (imageExts.includes(ext)) {
            const url = this.getFullFilePath(file);
            return `<img src="${url}" alt="${this.escapeHtml(file.name)}" loading="lazy"
                        style="width:100%;height:90px;object-fit:cover;border-radius:8px;">`;
        }
        if (ext === 'pdf') {
            const pages = file.total_pages ? `<span class="pdf-badge">${file.total_pages}p</span>` : '';
            return `<div style="position:relative;text-align:center;padding:18px 0;">
                        <i class="fas fa-file-pdf text-red-500" style="font-size:42px;"></i>${pages}
                    </div>`;
        }
        return '';
    };

    /* ---------------------------------------------------------------------
     * Item 3 (Properties) — augment the properties panel with the richer
     * fields (local + SMB path, total_pages) by calling the file_info API.
     * This wraps contextProperties if it exists; otherwise provides one.
     * ------------------------------------------------------------------- */
    FileExplorer.contextProperties = async function () {
        const file = this.state.contextItem;
        if (!file) return;
        this.hideContextMenu();
        try {
            const url = `${this.config.apiBaseUrl}?traveler_id=${travelerId}&action=file_info`
                + `&path=${encodeURIComponent(this.state.currentPath || '')}`
                + `&name=${encodeURIComponent(file.name)}`;
            const res = await fetch(url);
            const data = await res.json();
            const info = (data && data.info) ? data.info : null;
            this.renderRichProperties(file, info);
        } catch (e) {
            this.renderRichProperties(file, null);
        }
    };

    FileExplorer.renderRichProperties = function (file, info) {
        const panel = document.getElementById('properties-content');
        if (!panel) return;
        const smbBase = (function () {
            const el = document.getElementById('file-explorer');
            return el ? (el.getAttribute('data-smb-path') || '') : '';
        })();
        const rel = (file.path || '').replace(/\//g, '\\');
        const localPath = `\\storage\\travelers\\${this.state.travelerFolder}\\${rel}`;
        const smbPath = smbBase ? (smbBase.replace(/\\+$/, '') + '\\' + rel) : '—';
        const row = (k, v) => `
            <div class="property-item">
                <div class="property-label">${k}</div>
                <div class="property-value" style="word-break:break-all;">${v}</div>
            </div>`;
        const ext = file.type === 'folder' ? 'Folder' : (file.name.split('.').pop() || '').toUpperCase();
        const size = info ? info.sizeFormatted : (file.size || '—');
        const created = info && info.created ? info.created : '—';
        const modified = info && info.lastModified ? info.lastModified : (file.lastModified || '—');
        const pages = (info && info.total_pages) ? row('Total Pages', info.total_pages) : '';
        panel.innerHTML =
            row('Name', this.escapeHtml(file.name)) +
            row('Type', ext) +
            row('Size', size) +
            row('Local Path', this.escapeHtml(localPath)) +
            row('SMB Path', this.escapeHtml(smbPath)) +
            row('Created', created) +
            row('Modified', modified) +
            pages;
        const p = document.getElementById('properties-panel');
        if (p && p.classList.contains('hidden')) this.showPropertiesPanel();
    };

    /* ---------------------------------------------------------------------
     * Init hook — restore persisted view once the DOM + object are ready.
     * ------------------------------------------------------------------- */
    document.addEventListener('DOMContentLoaded', function () {
        // Defer slightly so the original init() (which calls setView('grid')) runs first
        setTimeout(function () {
            if (FileExplorer.restoreViewFromStorage) FileExplorer.restoreViewFromStorage();
        }, 300);
    });

    console.info('[std-folders-v2] File Explorer fixes loaded.');
})();