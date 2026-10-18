<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:899";
}
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

                        <!-- LEFT: Upload Form -->
                        <div class="col-span">
                            <h2 class="text-lg font-semibold text-gray-800 mb-1">
                                Passport Data Extraction
                            </h2>
                            <p class="text-sm text-gray-600 mb-4">
                                Drag & drop files or paste content from clipboard to get extracted data
                            </p>

                            <!-- Airline Code Input -->
                            <div class="mb-4 flex items-center gap-3">
                                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Airline Code:</label>
                                <input
                                    id="airlineCodeInput"
                                    type="text"
                                    value="MH"
                                    maxlength="2"
                                    placeholder="e.g. EK, SQ, BG"
                                    class="w-24 px-3 py-1.5 border border-gray-300 rounded-lg text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-400"
                                    oninput="this.value = this.value.toUpperCase()"
                                />
                                <span class="text-xs text-gray-400">2-letter IATA code</span>
                            </div>

                            <form id="taskForm">
                                <label class="block text-sm font-medium text-gray-700 my-2">Upload or Paste Your Documents</label>
                                <div class="grid grid-cols-2 gap-4">

                                    <!-- Drag & Drop -->
                                    <div>
                                        <div id="dragDropArea" class="rounded-lg border-2 border-dashed border-gray-300 p-6 mb-4 flex flex-col items-center justify-center hover:bg-gray-50 cursor-pointer">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                            <input type="file" id="fileInput" multiple class="hidden">
                                            <button
                                                type="button"
                                                onclick="document.getElementById('fileInput').click()"
                                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                                Browse Files
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Paste Area -->
                                    <div>
                                        <textarea id="pasteArea"
                                            placeholder="Paste content here"
                                            class="w-full h-36 p-2 border-2 border-dashed border-gray-300 rounded"></textarea>
                                    </div>

                                    <!-- File List -->
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

                                <button type="submit" class="flex-1 px-4 py-2 mt-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">
                                    <i class="fas fa-search mr-2"></i> Extract
                                </button>
                            </form>
                        </div>

                        <!-- RIGHT: Results -->
                        <div class="col-span">
                            <div id="passportCards" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        const URL_FOR_EXTRACTION = "<?php echo $ip_port; ?>" + `api/passports/extracted_data.php`;

        // ── File state ───────────────────────────────────────────────────
        let droppedFiles = [];
        const form        = document.getElementById('taskForm');
        const pasteArea   = document.getElementById('pasteArea');

        // ── Drag & Drop init ─────────────────────────────────────────────
        function initDragDrop() {
            const dragDropArea = document.getElementById('dragDropArea');
            const fileInput    = document.getElementById('fileInput');

            dragDropArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                dragDropArea.classList.add('dragover');
            });

            dragDropArea.addEventListener('dragleave', () => {
                dragDropArea.classList.remove('dragover');
            });

            dragDropArea.addEventListener('drop', (e) => {
                e.preventDefault();
                dragDropArea.classList.remove('dragover');
                if (e.dataTransfer.files.length > 0) handleFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleFiles(e.target.files);
                    e.target.value = '';
                }
            });

            dragDropArea.addEventListener('click', (e) => {
                if (e.currentTarget === e.target) fileInput.click();
            });
        }

        // ── Handle files ─────────────────────────────────────────────────
        function handleFiles(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const exists = droppedFiles.findIndex(f => f.name === file.name && f.size === file.size);
                if (exists === -1) {
                    droppedFiles.push(file);
                    addFileToList(file);
                } else {
                    alert(`File "${file.name}" already exists!`);
                }
            }
            updateFileCount();
        }

        function addFileToList(file) {
            const filesList = document.getElementById('droppedFilesList');

            if (filesList.children.length === 1 && filesList.children[0].classList.contains('text-center')) {
                filesList.innerHTML = '';
            }

            const fileItem = document.createElement('div');
            fileItem.className = 'file-item bg-white border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition duration-200 cursor-pointer mb-2';
            fileItem.dataset.fileName = file.name;
            fileItem.dataset.fileSize = file.size;
            fileItem.dataset.fileType = file.type;

            const fileIcon = getFileIcon(file.type);
            const isImage  = file.type.startsWith('image/');

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
                        <button onclick="previewInNewTab('${file.name}')" class="text-blue-500 hover:text-blue-700" title="Preview">
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

            fileItem.addEventListener('click', (e) => {
                if (!e.target.closest('button')) previewInNewTab(file.name);
            });

            filesList.appendChild(fileItem);
        }

        function getFileIcon(fileType) {
            if (fileType.startsWith('image/'))   return 'fas fa-file-image';
            if (fileType.startsWith('video/'))   return 'fas fa-file-video';
            if (fileType.startsWith('audio/'))   return 'fas fa-file-audio';
            if (fileType.includes('pdf'))        return 'fas fa-file-pdf';
            if (fileType.includes('word'))       return 'fas fa-file-word';
            if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'fas fa-file-excel';
            if (fileType.includes('zip') || fileType.includes('compressed'))    return 'fas fa-file-archive';
            if (fileType.startsWith('text/'))    return 'fas fa-file-alt';
            return 'fas fa-file';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k     = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i     = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function updateFileCount() {
            const countEl  = document.getElementById('fileCount');
            const fileCount = droppedFiles.length;
            countEl.textContent = `${fileCount} file${fileCount !== 1 ? 's' : ''}`;
            if (fileCount === 0)      countEl.className = 'text-xs bg-gray-200 px-2 py-1 rounded';
            else if (fileCount < 5)   countEl.className = 'text-xs bg-green-100 text-green-800 px-2 py-1 rounded';
            else                      countEl.className = 'text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded';
        }

        function removeFile(fileName) {
            if (!confirm('Are you sure you want to remove this file?')) return;
            droppedFiles = droppedFiles.filter(f => f.name !== fileName);
            updateFileCount();
            const filesList = document.getElementById('droppedFilesList');
            const items     = filesList.getElementsByClassName('file-item');
            for (let i = 0; i < items.length; i++) {
                if (items[i].dataset.fileName === fileName) { items[i].remove(); break; }
            }
            if (droppedFiles.length === 0) showEmptyFilesPlaceholder();
        }

        function downloadFile(fileName) {
            const file = droppedFiles.find(f => f.name === fileName);
            if (!file) return;
            const url = URL.createObjectURL(file);
            const a   = document.createElement('a');
            a.href     = url;
            a.download = file.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function clearAllFiles() {
            if (droppedFiles.length === 0) { alert('No files to clear!'); return; }
            if (confirm(`Remove all ${droppedFiles.length} files?`)) {
                droppedFiles = [];
                showEmptyFilesPlaceholder();
                updateFileCount();
            }
        }

        function showEmptyFilesPlaceholder() {
            document.getElementById('droppedFilesList').innerHTML = `
                <div class="text-center text-gray-500 py-4 text-sm">
                    <i class="fas fa-file mb-1"></i>
                    <p>No files added yet</p>
                </div>
            `;
        }

        // ── Paste handler ────────────────────────────────────────────────
        pasteArea.addEventListener('paste', (e) => {
            const items    = e.clipboardData.items;
            let hasFiles   = false;
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                if (item.kind === 'file') {
                    hasFiles = true;
                    const blob = item.getAsFile();
                    const file = new File([blob], blob.name || `pasted_file_${Date.now()}`, {
                        type: blob.type,
                        lastModified: Date.now()
                    });
                    const exists = droppedFiles.findIndex(f => f.name === file.name && f.size === file.size);
                    if (exists === -1) {
                        droppedFiles.push(file);
                        addFileToList(file);
                        updateFileCount();
                    } else {
                        alert(`File "${file.name}" already exists!`);
                    }
                }
            }
            if (hasFiles) e.preventDefault();
        });

        // ── Preview in new tab ───────────────────────────────────────────
        function previewInNewTab(fileName) {
            const file = droppedFiles.find(f => f.name === fileName);
            if (!file) return;

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const newTab = window.open('', '_blank');
                    newTab.document.write(`<!DOCTYPE html><html><head><title>Preview: ${file.name}</title>
                        <style>body{margin:0;padding:20px;background:#f0f0f0;display:flex;justify-content:center;align-items:center;min-height:100vh;}
                        .preview-container{max-width:90%;background:white;padding:20px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.2);text-align:center;}
                        img{max-width:100%;max-height:70vh;border-radius:5px;}.file-info{margin-top:15px;padding:10px;background:#f8f9fa;border-radius:5px;}</style>
                        </head><body><div class="preview-container"><img src="${e.target.result}" alt="${file.name}">
                        <div class="file-info"><h3>${file.name}</h3><p>Size: ${formatFileSize(file.size)} | Type: ${file.type}</p></div>
                        </div></body></html>`);
                    newTab.document.close();
                };
                reader.readAsDataURL(file);

            } else if (file.type.includes('pdf')) {
                const pdfUrl = URL.createObjectURL(file);
                const newTab = window.open('', '_blank');
                newTab.document.write(`<!DOCTYPE html><html><head><title>PDF: ${file.name}</title>
                    <style>body{margin:0;padding:20px;background:#f0f0f0;}
                    .pdf-container{width:100%;height:90vh;background:white;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.2);}
                    iframe{width:100%;height:100%;border:none;}</style>
                    </head><body><div class="pdf-container"><iframe src="${pdfUrl}"></iframe></div></body></html>`);
                newTab.document.close();

            } else {
                const newTab = window.open('', '_blank');
                newTab.document.write(`<!DOCTYPE html><html><head><title>File: ${file.name}</title>
                    <style>body{margin:0;padding:20px;background:#f0f0f0;display:flex;justify-content:center;align-items:center;min-height:100vh;}
                    .info-container{background:white;padding:30px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.2);text-align:center;max-width:500px;}
                    .file-icon{font-size:48px;color:#666;margin-bottom:20px;}</style></head>
                    <body><div class="info-container"><div class="file-icon"><i class="${getFileIcon(file.type)}"></i></div>
                    <h2>${file.name}</h2><p><strong>Type:</strong> ${file.type || 'Unknown'}</p>
                    <p><strong>Size:</strong> ${formatFileSize(file.size)}</p>
                    <p style="color:#666;margin-top:20px;">This file type cannot be previewed in the browser.</p>
                    <button onclick="window.location.href='${URL.createObjectURL(file)}';"
                        style="margin-top:20px;padding:10px 20px;background:#3b82f6;color:white;border:none;border-radius:5px;cursor:pointer;">
                        <i class="fas fa-download"></i> Download File</button>
                    </div></body></html>`);
                newTab.document.close();
            }
        }

        // ── Form submit ──────────────────────────────────────────────────
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData  = new FormData();
            const pasteText = document.getElementById('pasteArea').value;
            formData.append('pasted_text', pasteText);

            if (droppedFiles.length > 0) {
                droppedFiles.forEach(file => formData.append('files[]', file));
            }

            const submitBtn   = form.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.innerHTML  = '<i class="fas fa-spinner fa-spin mr-2"></i> Extracting...';
            submitBtn.disabled   = true;

            fetch(URL_FOR_EXTRACTION, { method: 'POST', body: formData })
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    console.log('API Response:', data);
                    if (data.success) {
                        renderPassportCards(data.data);
                        form.reset();
                        droppedFiles = [];
                        updateFileCount();
                        showEmptyFilesPlaceholder();
                    } else {
                        alert(data.message || 'Something went wrong');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Server or network error. Please try again. ' + err.message);
                })
                .finally(() => {
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled  = false;
                });
        });

        // ── Date formatter: YYYY-MM-DD → DDMMMYY ────────────────────────
        function formatGDSDate(dateStr) {
            if (!dateStr) return '';
            const months = ['JAN','FEB','MAR','APR','MAY','JUN',
                            'JUL','AUG','SEP','OCT','NOV','DEC'];

            // ISO: 1985-02-14
            const isoMatch = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (isoMatch) {
                const day = isoMatch[3].padStart(2, '0');
                const mon = months[parseInt(isoMatch[2]) - 1];
                const yr  = isoMatch[1].slice(2);
                return `${day}${mon}${yr}`;
            }

            // DDMMMYYYY → DDMMMYY
            const longMatch = dateStr.match(/^(\d{2})([A-Z]{3})(\d{4})$/i);
            if (longMatch) return `${longMatch[1]}${longMatch[2].toUpperCase()}${longMatch[3].slice(2)}`;

            // Already DDMMMYY
            const shortMatch = dateStr.match(/^(\d{2})([A-Z]{3})(\d{2})$/i);
            if (shortMatch) return dateStr.toUpperCase();

            return dateStr;
        }

        // ── SSR DOCS line builder ────────────────────────────────────────
        function buildSSRDocs(p, paxIndex) {
            const airline = (document.getElementById('airlineCodeInput')?.value || 'MH')
                                .toUpperCase().trim().slice(0, 2);

            const dob    = formatGDSDate(p.date_of_birth);
            const expiry = formatGDSDate(p.date_of_expiry);
            const gender = (p.gender || 'M')[0].toUpperCase();
            const cc     = (p.country_code || 'BGD').toUpperCase();
            const pno    = (p.passport_no  || '').toUpperCase();
            const sname  = (p.sur_name     || '').toUpperCase().replace(/\s+/g, '');
            const gname  = (p.given_name   || '').toUpperCase().trim();

            // nationality: use country_code (3-letter) — "BANGLADESHI" → "BGD"
            const nat = cc;

            return `SI.P${paxIndex}/SSRDOCS${airline}HK1/P/${cc}/${pno}/${nat}/${dob}/${gender}/${expiry}/${sname}/${gname}`;
        }

        // ── Field row helper ─────────────────────────────────────────────
        function fieldRow(label, value) {
            const safe = (value || '-').toString().replace(/'/g, "\\'");
            return `
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <span class="text-xs text-gray-500">${label}</span><br>
                        <span class="font-medium">${value || '-'}</span>
                    </div>
                    <button onclick="copyText('${safe}')" class="text-gray-400 hover:text-black text-sm flex-shrink-0" title="Copy">
                        📋
                    </button>
                </div>
            `;
        }

        // ── Render passport cards ────────────────────────────────────────
        function renderPassportCards(passports) {
            const container = document.getElementById('passportCards');
            container.innerHTML = '';

            passports.forEach((p, index) => {
                const ssrLine  = buildSSRDocs(p, index + 1);
                const safeDoc  = (p.doc_code || '').replace(/'/g, "\\'");

                const card = document.createElement('div');
                card.className = 'border rounded-lg p-4 shadow-sm bg-white';

                card.innerHTML = `
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-semibold text-sm">Passport #${index + 1}</h3>
                        <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded">
                            ${p.doc_code_short || 'P'}
                        </span>
                    </div>

                    <div class="text-sm space-y-2">
                        ${fieldRow('Country Code',  p.country_code)}
                        ${fieldRow('Salutation',    p.salutation)}
                        ${fieldRow('Given Name',    p.given_name)}
                        ${fieldRow('Sur Name',      p.sur_name)}
                        ${fieldRow('Passport No',   p.passport_no)}
                        ${fieldRow('Nationality',   p.nationality)}
                        ${fieldRow('Gender',        p.gender)}
                        ${fieldRow('Date of Birth', p.date_of_birth)}
                        ${fieldRow('Expiry Date',   p.date_of_expiry)}

                        <!-- DOC CODE -->
                        <div class="mt-3">
                            <div class="text-xs font-semibold text-gray-600 mb-1">DOC CODE</div>
                            <div class="flex items-center bg-gray-100 rounded p-2 text-xs break-all">
                                <span class="flex-1 font-mono">${p.doc_code || '-'}</span>
                                <button onclick="copyText('${safeDoc}')"
                                        class="ml-2 text-gray-500 hover:text-black flex-shrink-0" title="Copy">
                                    📋
                                </button>
                            </div>
                        </div>

                        <!-- SSR DOCS -->
                        <div class="mt-2">
                            <div class="text-xs font-semibold text-gray-600 mb-1">SSR DOCS</div>
                            <div class="flex items-center bg-green-50 border border-green-200 rounded p-2 text-xs break-all">
                                <span class="flex-1 font-mono" id="ssr_${index}">${ssrLine}</span>
                                <button onclick="copyText(document.getElementById('ssr_${index}').textContent)"
                                        class="ml-2 text-gray-500 hover:text-black flex-shrink-0" title="Copy">
                                    📋
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                container.appendChild(card);
            });
        }

        // ── Copy helper ──────────────────────────────────────────────────
        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Brief visual feedback — no alert
                const btn = event.currentTarget;
                const orig = btn.textContent;
                btn.textContent = '✅';
                setTimeout(() => btn.textContent = orig, 1200);
            }).catch(() => {
                alert('Copy failed. Please copy manually.');
            });
        }

        // ── Init ─────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            initDragDrop();

            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'd') { e.preventDefault(); clearAllFiles(); }
                if (e.key === 'Escape' && document.activeElement === pasteArea) pasteArea.value = '';
            });
        });
    </script>

</body>
</html>