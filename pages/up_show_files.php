<?php
/**
 * up_show_files.php - PDF to Image Conversion, File Management, and Deletion
 */
require_once 'live_storage.php';

// Increase limits for processing PDFs
ini_set('memory_limit', '1024M');
set_time_limit(600);

// Disable error reporting for standard output to keep JSON clean
error_reporting(0);
ini_set('display_errors', 0);

$omv = new OMV_SMB_Manager();
$base_dir = "rnd_traveler";

// Get parameters
$traveler_folder = isset($_GET['folder']) ? $_GET['folder'] : '';
$current_path = $base_dir . '/' . $traveler_folder . '/all_documents';

// --- 1. HANDLE DELETE ACTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json');
    $pathToDelete = $_POST['file_path'] ?? '';
    if ($pathToDelete) {
        $result = $omv->delete_file($pathToDelete);
        echo json_encode(['success' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No path provided']);
    }
    exit;
}

// --- 2. HANDLE FILE UPLOADS & PDF CONVERSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    header('Content-Type: application/json');
    $success = true;
    $errors = [];

    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
        if (empty($tmpName)) continue;

        $fileName = $_FILES['files']['name'][$key];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);

        // Standard Images
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $remotePath = $current_path . '/' . $fileName;
            $result = $omv->paste_file($tmpName, $remotePath);
            if ($result !== true) { $success = false; $errors[] = "Upload failed: $fileName"; }
        } 
        // PDF Conversion
        elseif ($ext === 'pdf') {
            try {
                $imagick = new Imagick();
                $imagick->setResolution(150, 150);
                // The [0] tells Imagick to read the PDF pages. 
                // Note: Ghostscript must be installed on the server for this to work.
                $imagick->readImage($tmpName); 
                
                foreach ($imagick as $i => $page) {
                    $page->setImageFormat('png');
                    $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
                    
                    $outputName = $baseName . "_" . ($i + 1) . ".png";
                    $remotePath = $current_path . '/' . $outputName;
                    
                    $tempPng = tempnam(sys_get_temp_dir(), 'pdf_pg_') . '.png';
                    $page->writeImage($tempPng);
                    
                    $uploadRes = $omv->paste_file($tempPng, $remotePath);
                    if ($uploadRes !== true) { $success = false; }
                    
                    if (file_exists($tempPng)) unlink($tempPng);
                }
                $imagick->clear();
                $imagick->destroy();
            } catch (Exception $e) { 
                $success = false; 
                $errors[] = "PDF Error: " . $e->getMessage(); 
            }
        }
    }
    echo json_encode(['success' => $success, 'errors' => $errors]);
    exit;
}

// List files for display
$files = $omv->list_files($current_path);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents - <?php echo htmlspecialchars($traveler_folder); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        .drag-over { border-color: #3b82f6; background-color: #eff6ff; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 50; align-items: center; justify-content: center; }
        .transparency-check { background-image: linear-gradient(45deg, #e5e7eb 25%, transparent 25%), linear-gradient(-45deg, #e5e7eb 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e5e7eb 75%), linear-gradient(-45deg, transparent 75%, #e5e7eb 75%); background-size: 10px 10px; background-position: 0 0, 0 5px, 5px 5px, 5px 0; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-50 p-4 md:p-8 font-sans">
    <div class="max-w-6xl mx-auto">
        
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 flex items-center justify-between">
            <div class="text-sm">
                <a href="index.php" class="text-blue-600 font-bold">TRAVELERS</a>
                <span class="mx-2 text-gray-300">/</span>
                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($traveler_folder); ?></span>
            </div>
            <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-xs font-bold transition-all">BACK TO LIST</a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border-2 border-dashed border-gray-200 p-8 mb-8 text-center" id="dropZone">
            <input type="file" id="fileInput" multiple class="hidden" accept=".jpg,.jpeg,.png,.pdf">
            <div class="text-4xl mb-3">📄</div>
            <h2 class="text-lg font-bold text-gray-800">Upload Images or PDF</h2>
            <p class="text-gray-400 text-xs mb-5">PDFs are automatically split into high-quality images</p>
            <button onclick="document.getElementById('fileInput').click()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-bold text-sm">SELECT FILES</button>
            <div id="uploadStatus" class="mt-3 text-xs font-bold"></div>
        </div>

        <div id="gallery" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <?php if (empty($files)): ?>
                <div class="col-span-full py-10 text-center text-gray-400">No documents found.</div>
            <?php else: ?>
                <?php foreach ($files as $file): 
                    $full_path = $current_path . '/' . $file; ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group">
                        <div class="aspect-w-1 aspect-h-1 relative overflow-hidden bg-gray-100 transparency-check">
                            <img src="smb_proxy.php?path=<?php echo urlencode($full_path); ?>&t=<?php echo time(); ?>" 
                                 class="w-full h-40 object-contain transition-transform group-hover:scale-105">
                        </div>
                        <div class="p-3">
                            <p class="text-[10px] font-bold text-gray-600 truncate mb-3"><?php echo htmlspecialchars($file); ?></p>
                            <div class="grid grid-cols-1 gap-2">
                                <a href="editor.php?img=<?php echo urlencode($full_path); ?>" 
                                   class="bg-blue-500 hover:bg-blue-600 text-white text-center py-2 rounded-lg text-[10px] font-bold">EDIT</a>
                                
                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="openTransferModal('<?php echo addslashes($full_path); ?>')" 
                                            class="bg-yellow-400 hover:bg-yellow-500 text-black py-2 rounded-lg text-[10px] font-bold">MOVE</button>
                                    <button onclick="confirmDelete('<?php echo addslashes($full_path); ?>')" 
                                            class="bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg text-[10px] font-bold">DELETE</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="transferModal" class="modal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl mx-4">
            <h3 class="text-lg font-bold mb-1">Move Document</h3>
            <p id="targetFileName" class="text-[10px] text-gray-400 truncate mb-5"></p>
            <input type="hidden" id="sourcePath">
            
            <div class="space-y-2">
                <button onclick="selectCategory('nid')" class="w-full border p-3 rounded-xl text-left hover:bg-blue-50 text-xs font-bold">NID Card</button>
                <div class="border rounded-xl overflow-hidden">
                    <button onclick="document.getElementById('passList').classList.toggle('hidden')" class="w-full p-3 text-left hover:bg-blue-50 text-xs font-bold flex justify-between">
                        <span>Passports</span> <span>▼</span>
                    </button>
                    <div id="passList" class="hidden bg-gray-50 max-h-40 overflow-y-auto custom-scrollbar border-t">
                        <?php for($i=1; $i<=10; $i++): ?>
                            <button onclick="selectCategory('passports/passport<?php echo $i; ?>')" class="w-full p-2 text-left hover:bg-blue-100 text-[10px] font-bold pl-6 border-b border-gray-100">Passport <?php echo $i; ?></button>
                        <?php endfor; ?>
                    </div>
                </div>
                <button onclick="selectCategory('office_document')" class="w-full border p-3 rounded-xl text-left hover:bg-blue-50 text-xs font-bold">Office Document</button>
                <button onclick="selectCategory('others')" class="w-full border p-3 rounded-xl text-left hover:bg-blue-50 text-xs font-bold">Others</button>
            </div>
            <button onclick="closeModal()" class="w-full mt-4 py-2 text-xs font-bold text-gray-400">CANCEL</button>
        </div>
    </div>

    <script>
        const traveler = "<?php echo $traveler_folder; ?>";
        const dropZone = document.getElementById('dropZone');

        // --- UPLOAD LOGIC ---
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('bg-blue-50'); });
        dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('bg-blue-50'); });
        dropZone.addEventListener('drop', (e) => { e.preventDefault(); handleUpload(e.dataTransfer.files); });
        document.getElementById('fileInput').addEventListener('change', (e) => handleUpload(e.target.files));

        async function handleUpload(files) {
            const status = document.getElementById('uploadStatus');
            status.innerHTML = '<span class="text-blue-600 animate-pulse">CONVERTING & UPLOADING...</span>';
            const formData = new FormData();
            for (let f of files) formData.append('files[]', f);

            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const data = await res.json();
            if(data.success) {
                status.innerHTML = '<span class="text-green-600">SUCCESS! REFRESHING...</span>';
                setTimeout(() => location.reload(), 1000);
            } else {
                status.innerHTML = '<span class="text-red-600">ERROR: Check server logs.</span>';
                console.error(data.errors);
            }
        }

        // --- DELETE LOGIC ---
        async function confirmDelete(path) {
            if (confirm("Are you sure you want to permanently delete this file?")) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('file_path', path);

                const res = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) {
                    location.reload();
                } else {
                    alert("Delete failed.");
                }
            }
        }

        // --- MODAL & MOVE LOGIC ---
        function openTransferModal(path) {
            document.getElementById('sourcePath').value = path;
            document.getElementById('targetFileName').textContent = path.split('/').pop();
            document.getElementById('transferModal').style.display = 'flex';
        }

        function closeModal() { document.getElementById('transferModal').style.display = 'none'; }

        async function selectCategory(cat) {
            const formData = new FormData();
            formData.append('source', document.getElementById('sourcePath').value);
            formData.append('traveler', traveler);
            formData.append('category', cat);

            const res = await fetch('smb_move.php', { method: 'POST', body: formData });
            const data = await res.json();
            if(data.success) location.reload();
        }
    </script>
</body>
</html>