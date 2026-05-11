<?php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$storeTravelerApi = $ip_port . "api/travelers/store.php";
$extractApi = $ip_port . "api/travelers/extract-document.php";
$checkDuplicateApi = $ip_port . "api/travelers/check-duplicate.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Traveler - Smart Extraction</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    <?php include '../elements/header.php'; ?>
    <?php include '../elements/aside.php'; ?>

    <main id="mainContent" class="pt-16 pl-64 lg:mt-16 transition-all duration-300">
        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Add New Traveler</h1>
                    <p class="text-gray-600 mt-1">Upload document for smart extraction or enter details manually</p>
                </div>

                <!-- Mode Selection Tabs -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button onclick="switchMode('upload')" id="uploadTab" 
                                class="mode-tab active px-6 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                                <i class="fas fa-file-upload mr-2"></i>Upload & Extract
                            </button>
                            <button onclick="switchMode('manual')" id="manualTab"
                                class="mode-tab px-6 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-keyboard mr-2"></i>Manual Entry
                            </button>
                        </nav>
                    </div>

                    <!-- Upload & Extract Mode -->
                    <div id="uploadMode" class="p-6">
                        <!-- Document Type Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                            <div class="flex gap-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="uploadDocType" value="passport" checked 
                                        class="w-4 h-4 text-blue-600" onchange="onDocumentTypeChange()">
                                    <span class="ml-2"><i class="fas fa-passport mr-1"></i>Passport</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="uploadDocType" value="nid"
                                        class="w-4 h-4 text-blue-600" onchange="onDocumentTypeChange()">
                                    <span class="ml-2"><i class="fas fa-id-card mr-1"></i>NID</span>
                                </label>
                            </div>
                        </div>

                        <!-- File Upload Area -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Document</label>
                            <div id="dropZone" 
                                class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer bg-gray-50">
                                <input type="file" id="fileInput" accept=".jpg,.jpeg,.png,.pdf,.webp" class="hidden">
                                <div id="uploadPrompt">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-sm text-gray-600">Drag & drop file here or click to browse</p>
                                    <p class="text-xs text-gray-400 mt-1">Supports JPG, PNG, PDF, WebP</p>
                                </div>
                                <div id="uploadPreview" class="hidden">
                                    <img id="previewImage" class="max-h-48 mx-auto rounded-lg shadow mb-3" alt="Preview">
                                    <p id="fileName" class="text-sm text-gray-700 font-medium"></p>
                                    <button type="button" onclick="removeFile()" class="mt-2 text-xs text-red-600 hover:text-red-800">
                                        <i class="fas fa-times mr-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Extract Button -->
                        <button type="button" onclick="extractDocument()" id="extractBtn"
                            class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-magic mr-2"></i>Extract Information
                        </button>

                        <!-- Extraction Status -->
                        <div id="extractionStatus" class="hidden mt-4">
                            <div id="extractingLoader" class="hidden flex items-center gap-2 text-blue-600">
                                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                                <span>Extracting information from document...</span>
                            </div>
                            <div id="extractionSuccess" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                                <i class="fas fa-check-circle mr-2"></i><span id="extractionSuccessText"></span>
                            </div>
                            <div id="extractionError" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                <i class="fas fa-exclamation-circle mr-2"></i><span id="extractionErrorText"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Entry Mode -->
                    <div id="manualMode" class="hidden p-6">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                            <div class="flex gap-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="manualDocType" value="passport" checked
                                        class="w-4 h-4 text-blue-600" onchange="onDocumentTypeChange()">
                                    <span class="ml-2"><i class="fas fa-passport mr-1"></i>Passport</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="manualDocType" value="nid"
                                        class="w-4 h-4 text-blue-600" onchange="onDocumentTypeChange()">
                                    <span class="ml-2"><i class="fas fa-id-card mr-1"></i>NID</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="fullName" class="block text-sm font-medium text-gray-700 mb-1">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="fullName" name="full_name"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter full name" required>
                            </div>
                            <div>
                                <label for="dateOfBirth" class="block text-sm font-medium text-gray-700 mb-1">
                                    Date of Birth
                                </label>
                                <input type="text" id="dateOfBirth" name="date_of_birth"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                    placeholder="DD MMM YYYY (e.g., 31 DEC 1983)">
                            </div>
                            <div>
                                <label id="documentNumberLabel" class="block text-sm font-medium text-gray-700 mb-1">
                                    Passport Number
                                </label>
                                <input type="text" id="documentNumber" name="document_number"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter document number">
                            </div>
                        </div>
                        
                        <!-- Check Duplicate Button -->
                        <button type="button" onclick="checkDuplicate()" id="checkDuplicateBtn"
                            class="mt-4 px-4 py-2 border border-yellow-300 bg-yellow-50 text-yellow-700 rounded-md hover:bg-yellow-100">
                            <i class="fas fa-search mr-2"></i>Check for Duplicates
                        </button>
                    </div>
                </div>

                <!-- Duplicate Results Section -->
                <div id="duplicateResults" class="hidden bg-white rounded-lg shadow mb-6 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>Potential Duplicates Found
                    </h3>
                    <div id="duplicateList" class="space-y-3 mb-4"></div>
                    
                    <div class="border-t pt-4">
                        <p class="text-sm text-gray-600 mb-3">What would you like to do?</p>
                        <div class="flex gap-3 flex-wrap">
                            <button onclick="proceedWithCreation()" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2">
                                <i class="fas fa-check"></i>Yes, Create Anyway
                            </button>
                            <button onclick="modifyDetails()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-2">
                                <i class="fas fa-edit"></i>Modify Details
                            </button>
                            <button onclick="cancelDuplicates()" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-times"></i>No, Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Create Button -->
                <div class="flex justify-end space-x-3">
                    <button onclick="resetForm()" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Reset
                    </button>
                    <button onclick="createTraveler()" id="createBtn"
                        class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
                        <i class="fas fa-user-plus mr-2"></i>Create Traveler
                    </button>
                </div>

                <!-- Messages -->
                <div id="messageContainer" class="hidden mt-4">
                    <div id="successMessage" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <span id="successText"></span>
                    </div>
                    <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <span id="errorText"></span>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <div class="pt-16 pl-64 lg:mt-16 m-4 transition-all duration-300">
        <div class="p-6 bg-yellow-50">
            <p><i class="fas fa-info-circle mr-2 text-yellow-600"></i><strong>Running WorkFlow:</strong> Upload a Passport/Nid or Form Fillup->Extract Data->Find in DB->Create Traveler->Open Traveler->Info Update->Files Upload->Rename Files by Human->File Organizations by Human</p>
            <p><i class="fas fa-info-circle mr-2 text-yellow-600"></i><strong>Upcoming WorkFlow:</strong> Upload a Passport/Nid or Form Fillup->Extract Data->Find in DB->Create Traveler->Open Traveler->Info Update->Files Upload->Suggested File Name and Rename Files by System->File Organizations by System</p>
        </div>
    </div>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script>
        const EXTRACT_API = "<?php echo $extractApi; ?>";
        const STORE_API = "<?php echo $storeTravelerApi; ?>";
        const CHECK_DUPLICATE_API = "<?php echo $checkDuplicateApi; ?>";
    
        let currentMode = 'upload';
        let extractedData = null;
        let fullExtractedData = null;
        let uploadedFile = null;
        let uploadedFilePath = null;
        let isExtractionDone = false;
        let duplicateFound = false;
        let duplicateData = null;
        let forceCreate = false;
    
        // Update labels based on document type
        function onDocumentTypeChange() {
            const docType = getSelectedDocumentType();
            const label = document.getElementById('documentNumberLabel');
            const input = document.getElementById('documentNumber');
            
            if (docType === 'passport') {
                label.textContent = 'Passport Number';
                input.placeholder = 'Enter passport number';
            } else {
                label.textContent = 'NID Number';
                input.placeholder = 'Enter NID number';
            }
        }
    
        function getSelectedDocumentType() {
            if (currentMode === 'upload') {
                return document.querySelector('input[name="uploadDocType"]:checked').value;
            } else {
                return document.querySelector('input[name="manualDocType"]:checked').value;
            }
        }
    
        // Mode switching
        function switchMode(mode) {
            currentMode = mode;
            document.getElementById('uploadMode').classList.toggle('hidden', mode !== 'upload');
            document.getElementById('manualMode').classList.toggle('hidden', mode !== 'manual');
            
            const uploadTab = document.getElementById('uploadTab');
            const manualTab = document.getElementById('manualTab');
            
            if (mode === 'upload') {
                uploadTab.classList.add('text-blue-600', 'border-blue-600', 'active');
                uploadTab.classList.remove('text-gray-500', 'border-transparent');
                manualTab.classList.add('text-gray-500', 'border-transparent');
                manualTab.classList.remove('text-blue-600', 'border-blue-600', 'active');
            } else {
                manualTab.classList.add('text-blue-600', 'border-blue-600', 'active');
                manualTab.classList.remove('text-gray-500', 'border-transparent');
                uploadTab.classList.add('text-gray-500', 'border-transparent');
                uploadTab.classList.remove('text-blue-600', 'border-blue-600', 'active');
            }
            
            onDocumentTypeChange();
        }
    
        // File handling
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
    
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-blue-500', 'bg-blue-50'); });
        dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('border-blue-500', 'bg-blue-50'); });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => { if (e.target.files.length > 0) handleFile(e.target.files[0]); });
    
        function handleFile(file) {
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('Unsupported file type. Please upload JPG, PNG, PDF, or WebP.', 'error');
                return;
            }
    
            uploadedFile = file;
            isExtractionDone = false;
            extractedData = null;
            fullExtractedData = null;
    
            document.getElementById('uploadPrompt').classList.add('hidden');
            document.getElementById('uploadPreview').classList.remove('hidden');
            document.getElementById('fileName').textContent = file.name;
    
            if (file.type !== 'application/pdf') {
                const reader = new FileReader();
                reader.onload = (e) => { document.getElementById('previewImage').src = e.target.result; };
                reader.readAsDataURL(file);
            }
    
            hideExtractionMessages();
            document.getElementById('extractionStatus').classList.add('hidden');
        }
    
        function removeFile() {
            uploadedFile = null;
            uploadedFilePath = null;
            extractedData = null;
            fullExtractedData = null;
            isExtractionDone = false;
            fileInput.value = '';
            document.getElementById('uploadPrompt').classList.remove('hidden');
            document.getElementById('uploadPreview').classList.add('hidden');
            document.getElementById('extractionStatus').classList.add('hidden');
            hideExtractionMessages();
        }
    
        function hideExtractionMessages() {
            document.getElementById('extractingLoader').classList.add('hidden');
            document.getElementById('extractionSuccess').classList.add('hidden');
            document.getElementById('extractionError').classList.add('hidden');
        }
    
        // Extract document
        async function extractDocument() {
            if (!uploadedFile) { showMessage('Please upload a document first.', 'error'); return; }
    
            const btn = document.getElementById('extractBtn');
            btn.disabled = true;
            btn.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white inline-block mr-2"></div>Extracting...';
    
            document.getElementById('extractionStatus').classList.remove('hidden');
            hideExtractionMessages();
            document.getElementById('extractingLoader').classList.remove('hidden');
    
            const formData = new FormData();
            formData.append('file', uploadedFile);
            formData.append('document_type', getSelectedDocumentType());
    
            try {
                const response = await fetch(EXTRACT_API, { method: 'POST', body: formData });
                const result = await response.json();
    
                document.getElementById('extractingLoader').classList.add('hidden');
    
                if (result.success) {
                    extractedData = result.data;
                    fullExtractedData = result.full_extracted_data;
                    uploadedFilePath = result.file_path;
                    isExtractionDone = true;
    
                    // Populate form fields
                    document.getElementById('fullName').value = result.data.full_name || '';
                    document.getElementById('dateOfBirth').value = result.data.date_of_birth || '';
                    document.getElementById('documentNumber').value = result.data.document_number || '';
    
                    document.getElementById('extractionSuccess').classList.remove('hidden');
                    document.getElementById('extractionSuccessText').textContent = 
                        `Extracted: ${result.data.full_name || 'N/A'} | ${result.data.document_number || 'N/A'} | DOB: ${result.data.date_of_birth || 'N/A'}`;
                    
                    showMessage('Document extracted successfully! Review details below.', 'success');
                    
                    // Auto-check duplicates after extraction
                    setTimeout(() => checkDuplicate(), 500);
                } else {
                    document.getElementById('extractionError').classList.remove('hidden');
                    document.getElementById('extractionErrorText').textContent = result.message;
                    showMessage(result.message || 'Extraction failed', 'error');
                }
            } catch (error) {
                document.getElementById('extractingLoader').classList.add('hidden');
                document.getElementById('extractionError').classList.remove('hidden');
                document.getElementById('extractionErrorText').textContent = 'Network error: ' + error.message;
                showMessage('Network error: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic mr-2"></i>Extract Information';
            }
        }
    
        // Check for duplicates
        async function checkDuplicate() {
            const fullName = document.getElementById('fullName').value.trim();
            const documentNumber = document.getElementById('documentNumber').value.trim();
            const dateOfBirth = document.getElementById('dateOfBirth').value.trim();
            const documentType = getSelectedDocumentType();
    
            if (!fullName && !documentNumber && !dateOfBirth) {
                showMessage('Please enter at least one field to check for duplicates.', 'warning');
                return;
            }
    
            const btn = document.getElementById('checkDuplicateBtn');
            btn.disabled = true;
            btn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-yellow-600 inline-block mr-2"></div>Checking...';
    
            try {
                const response = await fetch(CHECK_DUPLICATE_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        full_name: fullName,
                        document_number: documentNumber,
                        document_type: documentType,
                        date_of_birth: dateOfBirth
                    })
                });
    
                const result = await response.json();
    
                if (result.has_duplicates) {
                    duplicateFound = true;
                    duplicateData = result.duplicates;
                    showDuplicateResults(result.duplicates);
                    
                    const exactMatches = result.duplicates.filter(d => d.match_type === 'exact');
                    const partialMatches = result.duplicates.filter(d => d.match_type === 'partial');
                    
                    if (exactMatches.length > 0) {
                        showMessage(`⚠️ Found ${exactMatches.length} exact match(es)! This traveler already exists.`, 'warning');
                    } else if (partialMatches.length > 0) {
                        showMessage(`🔍 Found ${partialMatches.length} partial match(es). Please verify if this is the same person.`, 'info');
                    }
                } else {
                    duplicateFound = false;
                    document.getElementById('duplicateResults').classList.add('hidden');
                    showMessage('✅ No duplicates found. Ready to create traveler!', 'success');
                }
            } catch (error) {
                showMessage('Error checking duplicates: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search mr-2"></i>Check for Duplicates';
            }
        }
    
        // Show duplicate results
        function showDuplicateResults(duplicates) {
            const container = document.getElementById('duplicateResults');
            const list = document.getElementById('duplicateList');
            
            list.innerHTML = duplicates.map(dup => `
                <div class="border ${dup.match_type === 'exact' ? 'border-red-300 bg-red-50' : 'border-yellow-300 bg-yellow-50'} rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${
                                    dup.match_type === 'exact' 
                                        ? 'bg-red-200 text-red-800' 
                                        : 'bg-yellow-200 text-yellow-800'
                                }">
                                    ${dup.match_type === 'exact' ? '⚠️ Exact Match' : '🔍 Partial Match'}
                                </span>
                                <span class="text-xs text-gray-500">${dup.match_reason || ''}</span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-gray-500">Name:</span>
                                    <span class="font-semibold text-gray-800">${dup.name || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">${dup.column === 'passport_no' ? 'Passport' : 'NID'}:</span>
                                    <span class="font-semibold text-gray-800">${dup.document_number || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">DOB:</span>
                                    <span class="font-semibold text-gray-800">${dup.date_of_birth || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Status:</span>
                                    <span class="font-semibold ${
                                        dup.status === 'active' ? 'text-green-600' : 'text-gray-600'
                                    }">${dup.status || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Created By:</span>
                                    <span class="text-gray-700">${dup.created_by || 'system'}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Created At:</span>
                                    <span class="text-gray-700">${dup.created_at || 'N/A'}</span>
                                </div>
                            </div>
                            
                            <div class="mt-2 text-xs text-gray-400">
                                Sys ID: ${dup.sys_id}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            container.classList.remove('hidden');
        }
    
        // Proceed with creation despite duplicates
        function proceedWithCreation() {
            forceCreate = true;
            document.getElementById('duplicateResults').classList.add('hidden');
            showMessage('Creating traveler with force override...', 'info');
            createTraveler();
        }
    
        // Cancel due to duplicates
        function cancelDuplicates() {
            document.getElementById('duplicateResults').classList.add('hidden');
            duplicateFound = false;
            showMessage('Creation cancelled. You can modify details and try again.', 'info');
        }
    
        // Modify details (keep form data, just hide duplicate results)
        function modifyDetails() {
            document.getElementById('duplicateResults').classList.add('hidden');
            duplicateFound = false;
            showMessage('You can now modify the details. The duplicate results have been hidden.', 'info');
            
            // Scroll back to the form
            document.getElementById('fullName').focus();
            document.getElementById('fullName').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    
        // Create traveler
        async function createTraveler() {
            if (duplicateFound && !forceCreate) {
                showMessage('Please review duplicates first. Choose an action below.', 'warning');
                return;
            }
    
            const fullName = document.getElementById('fullName').value.trim();
            const dateOfBirth = document.getElementById('dateOfBirth').value.trim();
            const documentNumber = document.getElementById('documentNumber').value.trim();
            const documentType = getSelectedDocumentType();
    
            if (!fullName) {
                showMessage('Full name is required.', 'error');
                return;
            }
    
            const btn = document.getElementById('createBtn');
            btn.disabled = true;
            btn.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white inline-block mr-2"></div>Creating...';
    
            const travelerData = {
                full_name: fullName,
                date_of_birth: dateOfBirth || null,
                document_type: documentType,
                document_number: documentNumber || null,
                file_path: null,
                extracted_data: null,
                force_create: forceCreate
            };
    
            if (currentMode === 'upload' && isExtractionDone && uploadedFilePath) {
                travelerData.file_path = uploadedFilePath;
                travelerData.extracted_data = fullExtractedData;
            }
    
            try {
                const response = await fetch(STORE_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(travelerData)
                });
    
                const result = await response.json();
    
                if (result.success) {
                    showMessage('Traveler created successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index-travelers.php';
                    }, 1500);
                } else {
                    showMessage(result.message || 'Failed to create traveler', 'error');
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus mr-2"></i>Create Traveler';
                forceCreate = false;
            }
        }
    
        // Reset form
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
                document.getElementById('fullName').value = '';
                document.getElementById('dateOfBirth').value = '';
                document.getElementById('documentNumber').value = '';
                removeFile();
                extractedData = null;
                fullExtractedData = null;
                isExtractionDone = false;
                duplicateFound = false;
                forceCreate = false;
                document.getElementById('duplicateResults').classList.add('hidden');
                document.getElementById('messageContainer').classList.add('hidden');
                showMessage('Form reset successfully.', 'success');
            }
        }
    
        // Messages
        function showMessage(message, type) {
            const container = document.getElementById('messageContainer');
            const successDiv = document.getElementById('successMessage');
            const errorDiv = document.getElementById('errorMessage');
            const successText = document.getElementById('successText');
            const errorText = document.getElementById('errorText');
    
            container.classList.remove('hidden');
    
            // Reset styles
            successDiv.className = 'hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative';
            errorDiv.className = 'hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative';
    
            if (type === 'success') {
                successDiv.classList.remove('hidden');
                successText.textContent = message;
            } else if (type === 'error') {
                errorDiv.classList.remove('hidden');
                errorText.textContent = message;
            } else if (type === 'warning') {
                errorDiv.classList.remove('hidden');
                errorDiv.className = 'bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative';
                errorText.textContent = message;
            } else if (type === 'info') {
                successDiv.classList.remove('hidden');
                successDiv.className = 'bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative';
                successText.textContent = message;
            }
    
            setTimeout(() => { container.classList.add('hidden'); }, 6000);
        }
    
        // Initialize
        onDocumentTypeChange();
    </script>
</body>
</html>