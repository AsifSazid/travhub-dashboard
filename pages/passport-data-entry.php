<?php

session_start();

require_once 'live_storage.php';
require_once 'db.php';

$filePath = $_GET['path'] ?? null;
if (!$filePath) die("No file path provided.");

$pathParts = explode('/', $filePath);
$traveler_id = $pathParts[1] ?? 'unknown';

$apiKey = 'AIzaSyAXoe0TCR5NmdqSFGj1Tr2ZVadRx6gDPbw';
$model = 'gemini-2.0-flash-lite';

// --- HANDLE SAVE DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_db') {
    header('Content-Type: application/json');
    $newEntry = json_decode($_POST['json_data'], true);
    $tid = $_POST['traveler_id'];
    $pageType = $_POST['page_type'] ?? 'unknown';
    
    // Add metadata to the entry
    $newEntry['_metadata'] = [
        'saved_at' => date('Y-m-d H:i:s'),
        'page_type' => $pageType,
        'source_file' => $filePath
    ];
    
    $stmt = $conn->prepare("SELECT passport FROM traveler_profile WHERE traveler_id = ?");
    $stmt->bind_param("s", $tid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $history = json_decode($row['passport'], true) ?: [];
        $history[] = $newEntry;
        $finalJson = json_encode($history);
        $update = $conn->prepare("UPDATE traveler_profile SET passport = ? WHERE traveler_id = ?");
        $update->bind_param("ss", $finalJson, $tid);
        $success = $update->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO traveler_profile (traveler_id, passport) VALUES (?, ?)");
        $jsonStr = json_encode([$newEntry]);
        $stmt->bind_param("ss", $tid, $jsonStr);
        $success = $stmt->execute();
    }
    echo json_encode(['success' => $success]);
    exit;
}

// --- HANDLE MANUAL PAGE TYPE SELECTION ---
$manualPageType = $_POST['manual_page_type'] ?? $_GET['manual_type'] ?? null;
$forceReprocess = isset($_POST['force_reprocess']) && $_POST['force_reprocess'] === 'true';

// --- SMB & AI PROCESSING ---
$omv = new OMV_SMB_Manager();
$tempLocalFile = tempnam(sys_get_temp_dir(), 'passport_');
$omv->get_file($filePath, $tempLocalFile);
$mimeType = (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'png') ? 'image/png' : 'image/jpeg';
$imageData = base64_encode(file_get_contents($tempLocalFile));
unlink($tempLocalFile);

// Enhanced prompt to extract ALL passport data including Bengali text
$prompt = "Analyze this passport/visa/immigration document image carefully. Extract ALL information visible on the page.

IMPORTANT INSTRUCTIONS:
1. Look for BOTH English and Bengali text in the document
2. Extract ALL personal details, family details, and contact information
3. For passports, look for fields like: Name, Father's Name, Mother's Name, Spouse's Name, Permanent Address, Emergency Contact
4. Extract the MRZ (Machine Readable Zone) lines completely
5. For dates, use format DD MMM YYYY (e.g., 13 MAY 1996)

CLASSIFY THE PAGE TYPE into one of these categories:
- **bio_page** - Passport bio-data page with personal information and MRZ
- **visa_page** - Visa sticker or visa page
- **arrival_page** - Immigration stamp page with entry/exit stamps
- **combined_page** - Contains both visa and stamps on same page

Return ONLY a JSON object with this exact structure:

For bio_page (PASSPORT):
{
    \"page_type\": \"bio_page\",
    \"bio_info\": {
        \"passport_number\": \"\",
        \"country_code\": \"\",
        \"surname\": \"\",
        \"given_names\": \"\",
        \"full_name\": \"\",
        \"nationality\": \"\",
        \"date_of_birth\": \"\",
        \"sex\": \"\",
        \"place_of_birth\": \"\",
        \"date_of_issue\": \"\",
        \"date_of_expiry\": \"\",
        \"issuing_authority\": \"\",
        \"mrz_line_1\": \"\",
        \"mrz_line_2\": \"\",
        \"father_name\": \"\",
        \"mother_name\": \"\",
        \"spouse_name\": \"\",
        \"permanent_address\": \"\",
        \"emergency_contact\": {
            \"name\": \"\",
            \"relationship\": \"\",
            \"address\": \"\",
            \"telephone\": \"\"
        }
    }
}

For visa_page:
{
    \"page_type\": \"visa_page\",
    \"visa_info\": {
        \"visa_number\": \"\",
        \"passport_number\": \"\",
        \"visa_type\": \"\",
        \"date_of_issue\": \"\",
        \"date_of_expiry\": \"\",
        \"maximum_stay\": \"\",
        \"endorsement\": \"\",
        \"visa_country_code\": \"\",
        \"entry_points\": \"\",
        \"restrictions\": \"\",
        \"registration_required\": \"\"
    }
}

For arrival_page:
{
    \"page_type\": \"arrival_page\",
    \"arrival_info\": {
        \"stamps\": [
            {
                \"type\": \"ARRIVAL\",
                \"date\": \"\",
                \"port\": \"\",
                \"immigration_code\": \"\",
                \"mode\": \"\"
            }
        ]
    }
}

For combined_page:
{
    \"page_type\": \"combined_page\",
    \"visa_info\": { ... },
    \"arrival_info\": { ... }
}

Extract ALL text accurately. If you see Bengali text like \"পিতা\", \"মাতা\", translate or include both. Return ONLY valid JSON.";

$payload = [
    "contents" => [[
        "parts" => [
            ["text" => $prompt],
            ["inline_data" => ["mime_type" => $mimeType, "data" => $imageData]]
        ]
    ]],
    "generationConfig" => ["response_mime_type" => "application/json"]
];

$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

$rawAiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
$cleanJson = preg_replace('/^```json|```$/m', '', $rawAiText);
$aiResponse = json_decode(trim($cleanJson), true) ?: [];

// Override page type if manually selected
if ($manualPageType && in_array($manualPageType, ['bio_page', 'visa_page', 'arrival_page', 'combined_page'])) {
    $pageType = $manualPageType;
} else {
    // Determine page type from AI response
    $pageType = isset($aiResponse['page_type']) ? $aiResponse['page_type'] : null;
    
    // Check for combined page (has both visa and stamp info)
    if ($pageType !== 'combined_page' && 
        isset($aiResponse['visa_info']) && !empty($aiResponse['visa_info']) &&
        isset($aiResponse['arrival_info']) && !empty($aiResponse['arrival_info']['stamps'])) {
        $pageType = 'combined_page';
        $aiResponse['page_type'] = 'combined_page';
    }
}

// Fallback logic if AI didn't detect page type
if (!$pageType) {
    if (isset($aiResponse['bio_info']) && (!empty($aiResponse['bio_info']['passport_number'] ?? '') || !empty($aiResponse['bio_info']['full_name'] ?? ''))) {
        $pageType = 'bio_page';
    } elseif (isset($aiResponse['visa_info']) && !empty($aiResponse['visa_info']['visa_number'] ?? '')) {
        $pageType = 'visa_page';
    } elseif (isset($aiResponse['arrival_info']) && !empty($aiResponse['arrival_info']['stamps'] ?? [])) {
        $pageType = 'arrival_page';
    } else {
        // Default based on filename pattern
        $fileName = basename($filePath);
        if (stripos($fileName, 'stamp') !== false || stripos($fileName, 'arrival') !== false) {
            $pageType = 'arrival_page';
        } elseif (stripos($fileName, 'visa') !== false || stripos($fileName, 'est') !== false) {
            $pageType = 'visa_page';
        } else {
            $pageType = 'bio_page';
        }
    }
}

// Ensure proper structure based on page type
if ($pageType === 'bio_page') {
    if (!isset($aiResponse['bio_info'])) {
        $aiResponse['bio_info'] = [];
    }
    
    // Ensure all fields exist
    $defaultBio = [
        'passport_number' => '', 'country_code' => '', 'surname' => '', 'given_names' => '',
        'full_name' => '', 'nationality' => '', 'date_of_birth' => '', 'sex' => '',
        'place_of_birth' => '', 'date_of_issue' => '', 'date_of_expiry' => '',
        'issuing_authority' => '', 'mrz_line_1' => '', 'mrz_line_2' => '',
        'father_name' => '', 'mother_name' => '', 'spouse_name' => '',
        'permanent_address' => '', 
        'emergency_contact' => ['name' => '', 'relationship' => '', 'address' => '', 'telephone' => '']
    ];
    
    $aiResponse['bio_info'] = array_merge($defaultBio, $aiResponse['bio_info']);
}

if (in_array($pageType, ['visa_page', 'combined_page']) && !isset($aiResponse['visa_info'])) {
    $aiResponse['visa_info'] = [
        'visa_number' => '', 'passport_number' => '', 'visa_type' => '',
        'date_of_issue' => '', 'date_of_expiry' => '', 'maximum_stay' => '',
        'endorsement' => '', 'visa_country_code' => '', 'entry_points' => '',
        'restrictions' => '', 'registration_required' => ''
    ];
}

if (in_array($pageType, ['arrival_page', 'combined_page']) && !isset($aiResponse['arrival_info'])) {
    $aiResponse['arrival_info'] = ['stamps' => []];
}

// Extract stamps data
$stamps = [];
if (isset($aiResponse['arrival_info']['stamps'])) {
    $stamps = $aiResponse['arrival_info']['stamps'];
}

// If no stamps detected but it's a combined page with visa, add empty stamp array
if (empty($stamps) && $pageType === 'combined_page') {
    $stamps = [['type' => 'ARRIVAL', 'date' => '', 'port' => '', 'immigration_code' => '', 'mode' => '']];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Document Verification - <?php echo ucfirst(str_replace('_', ' ', $pageType)); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        .sticky-col { position: sticky; top: 1rem; }
        .img-box img { transition: transform 0.3s ease; }
        .form-section-card { transition: all 0.2s ease; }
        .form-section-card:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        input:focus, textarea:focus, select:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); outline: none; }
        .page-type-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .select-page-type {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-full mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden grid lg:grid-cols-12 min-h-screen">
        
        <!-- Left Column: Image -->
        <div class="lg:col-span-7 p-6 bg-gray-900 overflow-y-auto">
            <div class="sticky-col">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex gap-2">
                        <span class="text-white text-xs font-bold uppercase tracking-widest bg-blue-600 px-3 py-1 rounded-full">Source Image</span>
                        <span class="page-type-badge text-white text-xs font-bold uppercase px-3 py-1 rounded-full"><?php echo strtoupper(str_replace('_', ' ', $pageType)); ?></span>
                    </div>
                    <button onclick="rotateImg()" class="text-white bg-gray-700 px-3 py-1 rounded-lg text-xs hover:bg-gray-600 transition">Rotate ↻</button>
                </div>
                
                <!-- Manual Page Type Selector -->
                <div class="mb-4 p-3 bg-gray-800 rounded-xl">
                    <label class="text-white text-xs font-bold uppercase block mb-2">📄 Manual Page Type Selection (if AI detection is incorrect)</label>
                    <div class="flex gap-2">
                        <select id="manualPageType" class="flex-1 bg-gray-700 text-white text-sm rounded-lg px-3 py-2 border border-gray-600 focus:border-blue-500">
                            <option value="bio_page" <?php echo $pageType === 'bio_page' ? 'selected' : ''; ?>>📘 Bio Page (Passport Info)</option>
                            <option value="visa_page" <?php echo $pageType === 'visa_page' ? 'selected' : ''; ?>>📋 Visa Page</option>
                            <option value="arrival_page" <?php echo $pageType === 'arrival_page' ? 'selected' : ''; ?>>🛂 Arrival/Stamp Page</option>
                            <option value="combined_page" <?php echo $pageType === 'combined_page' ? 'selected' : ''; ?>>🔗 Combined Page (Visa + Stamps)</option>
                        </select>
                        <button onclick="changePageType()" class="select-page-type text-white px-4 py-2 rounded-lg text-sm font-bold hover:opacity-90 transition">
                            Apply Change
                        </button>
                    </div>
                    <p class="text-gray-400 text-[10px] mt-2">⚠️ Use this if AI misidentified the document type. Page will reload with correct form.</p>
                </div>
                
                <div id="imgWrapper" class="img-box rounded-2xl overflow-hidden shadow-2xl bg-black flex items-center justify-center">
                    <img id="passportImg" src="data:<?php echo $mimeType; ?>;base64,<?php echo $imageData; ?>" class="w-full h-auto">
                </div>
            </div>
        </div>

        <!-- Right Column: Form -->
        <div class="lg:col-span-5 p-8 overflow-y-auto bg-white">
            <div class="flex justify-between items-center mb-6 pb-4 border-b">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 uppercase italic">Verification</h2>
                    <p class="text-[10px] font-bold text-blue-500 uppercase">Traveler: <?php echo htmlspecialchars($traveler_id); ?></p>
                </div>
                <button onclick="saveToDb()" id="saveBtn" class="bg-green-600 text-white px-8 py-3 rounded-2xl font-bold hover:bg-green-700 shadow-xl transition-all flex items-center gap-2">💾 SAVE DATA</button>
            </div>
            
            <form id="routerForm">
                <input type="hidden" name="page_type" value="<?php echo $pageType; ?>">
                
                <?php if ($pageType === 'bio_page'): 
                    $bio = $aiResponse['bio_info'];
                ?>
                    <!-- Passport Information Section -->
                    <div class="form-section-card mb-5 p-4 rounded-xl border border-gray-200 bg-gray-50">
                        <h3 class="text-xs font-black text-blue-600 uppercase mb-3 flex items-center gap-2"><span class="w-1 h-4 bg-blue-600 rounded-full"></span> PASSPORT INFORMATION</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Passport Number</label>
                                <input type="text" name="bio_info[passport_number]" value="<?php echo htmlspecialchars($bio['passport_number'] ?? ''); ?>" class="w-full bg-white text-sm font-mono font-bold text-gray-800 outline-none border-b border-gray-300 py-1 focus:border-blue-500">
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Country Code</label>
                                <input type="text" name="bio_info[country_code]" value="<?php echo htmlspecialchars($bio['country_code'] ?? ''); ?>" class="w-full bg-white text-sm font-medium text-gray-800 outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Surname</label>
                                <input type="text" name="bio_info[surname]" value="<?php echo htmlspecialchars($bio['surname'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Given Names</label>
                                <input type="text" name="bio_info[given_names]" value="<?php echo htmlspecialchars($bio['given_names'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Full Name</label>
                                <input type="text" name="bio_info[full_name]" value="<?php echo htmlspecialchars($bio['full_name'] ?? ''); ?>" class="w-full bg-white text-sm font-bold outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Nationality</label>
                                <input type="text" name="bio_info[nationality]" value="<?php echo htmlspecialchars($bio['nationality'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Date of Birth</label>
                                <input type="text" name="bio_info[date_of_birth]" value="<?php echo htmlspecialchars($bio['date_of_birth'] ?? ''); ?>" placeholder="DD MMM YYYY" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Sex</label>
                                <input type="text" name="bio_info[sex]" value="<?php echo htmlspecialchars($bio['sex'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Place of Birth</label>
                                <input type="text" name="bio_info[place_of_birth]" value="<?php echo htmlspecialchars($bio['place_of_birth'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Date of Issue</label>
                                <input type="text" name="bio_info[date_of_issue]" value="<?php echo htmlspecialchars($bio['date_of_issue'] ?? ''); ?>" placeholder="DD MMM YYYY" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Date of Expiry</label>
                                <input type="text" name="bio_info[date_of_expiry]" value="<?php echo htmlspecialchars($bio['date_of_expiry'] ?? ''); ?>" placeholder="DD MMM YYYY" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Issuing Authority</label>
                                <input type="text" name="bio_info[issuing_authority]" value="<?php echo htmlspecialchars($bio['issuing_authority'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Family Details -->
                    <div class="form-section-card mb-5 p-4 rounded-xl border border-gray-200 bg-gray-50">
                        <h3 class="text-xs font-black text-blue-600 uppercase mb-3 flex items-center gap-2"><span class="w-1 h-4 bg-blue-600 rounded-full"></span> FAMILY DETAILS</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Father's Name</label>
                                <input type="text" name="bio_info[father_name]" value="<?php echo htmlspecialchars($bio['father_name'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Mother's Name</label>
                                <input type="text" name="bio_info[mother_name]" value="<?php echo htmlspecialchars($bio['mother_name'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Spouse's Name</label>
                                <input type="text" name="bio_info[spouse_name]" value="<?php echo htmlspecialchars($bio['spouse_name'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Permanent Address</label>
                                <textarea name="bio_info[permanent_address]" rows="2" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1"><?php echo htmlspecialchars($bio['permanent_address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Emergency Contact -->
                    <div class="form-section-card mb-5 p-4 rounded-xl border border-gray-200 bg-gray-50">
                        <h3 class="text-xs font-black text-blue-600 uppercase mb-3 flex items-center gap-2"><span class="w-1 h-4 bg-blue-600 rounded-full"></span> EMERGENCY CONTACT</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Name</label>
                                <input type="text" name="bio_info[emergency_contact][name]" value="<?php echo htmlspecialchars($bio['emergency_contact']['name'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Relationship</label>
                                <input type="text" name="bio_info[emergency_contact][relationship]" value="<?php echo htmlspecialchars($bio['emergency_contact']['relationship'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Address</label>
                                <input type="text" name="bio_info[emergency_contact][address]" value="<?php echo htmlspecialchars($bio['emergency_contact']['address'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Telephone</label>
                                <input type="text" name="bio_info[emergency_contact][telephone]" value="<?php echo htmlspecialchars($bio['emergency_contact']['telephone'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                        </div>
                    </div>
                    
                    <!-- MRZ Section -->
                    <div class="form-section-card mb-5 p-4 rounded-xl border border-gray-200 bg-gray-50">
                        <h3 class="text-xs font-black text-blue-600 uppercase mb-3 flex items-center gap-2"><span class="w-1 h-4 bg-blue-600 rounded-full"></span> MACHINE READABLE ZONE</h3>
                        <div class="space-y-2">
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">MRZ Line 1</label>
                                <input type="text" name="bio_info[mrz_line_1]" value="<?php echo htmlspecialchars($bio['mrz_line_1'] ?? ''); ?>" class="w-full bg-white text-xs font-mono outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">MRZ Line 2</label>
                                <input type="text" name="bio_info[mrz_line_2]" value="<?php echo htmlspecialchars($bio['mrz_line_2'] ?? ''); ?>" class="w-full bg-white text-xs font-mono outline-none border-b border-gray-300 py-1">
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($pageType === 'visa_page'): 
                    $visa = $aiResponse['visa_info'];
                ?>
                    <!-- Visa Information Section -->
                    <div class="form-section-card mb-5 p-4 rounded-xl border-2 border-blue-100 bg-blue-50/30">
                        <h3 class="text-xs font-black text-blue-700 uppercase mb-3 flex items-center gap-2"><span class="w-1 h-4 bg-blue-700 rounded-full"></span> VISA INFORMATION</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[9px] font-bold text-red-600 uppercase">Visa Number *</label>
                                <input type="text" name="visa_info[visa_number]" value="<?php echo htmlspecialchars($visa['visa_number'] ?? ''); ?>" class="w-full bg-white text-sm font-mono font-bold outline-none border-b border-red-300 py-1 focus:border-red-500">
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[9px] font-bold text-red-600 uppercase">Visa Country Code *</label>
                                <input type="text" name="visa_info[visa_country_code]" value="<?php echo htmlspecialchars($visa['visa_country_code'] ?? ''); ?>" placeholder="e.g., IND, BGD, USA" class="w-full bg-white text-sm font-bold uppercase outline-none border-b border-red-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Passport Number</label>
                                <input type="text" name="visa_info[passport_number]" value="<?php echo htmlspecialchars($visa['passport_number'] ?? ''); ?>" class="w-full bg-white text-sm font-mono outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Visa Type</label>
                                <input type="text" name="visa_info[visa_type]" value="<?php echo htmlspecialchars($visa['visa_type'] ?? ''); ?>" placeholder="T-1, Tourist, Business" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Date of Issue</label>
                                <input type="text" name="visa_info[date_of_issue]" value="<?php echo htmlspecialchars($visa['date_of_issue'] ?? ''); ?>" placeholder="DD MMM YYYY" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Date of Expiry</label>
                                <input type="text" name="visa_info[date_of_expiry]" value="<?php echo htmlspecialchars($visa['date_of_expiry'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Maximum Stay</label>
                                <input type="text" name="visa_info[maximum_stay]" value="<?php echo htmlspecialchars($visa['maximum_stay'] ?? ''); ?>" placeholder="90 DAYS" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Endorsement</label>
                                <input type="text" name="visa_info[endorsement]" value="<?php echo htmlspecialchars($visa['endorsement'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Entry Points / Ports</label>
                                <input type="text" name="visa_info[entry_points]" value="<?php echo htmlspecialchars($visa['entry_points'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Restrictions</label>
                                <input type="text" name="visa_info[restrictions]" value="<?php echo htmlspecialchars($visa['restrictions'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Registration Required</label>
                                <input type="text" name="visa_info[registration_required]" value="<?php echo htmlspecialchars($visa['registration_required'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                        </div>
                    </div>
                    
                <?php elseif (in_array($pageType, ['arrival_page', 'combined_page'])): ?>
                    <!-- Immigration Stamps Section -->
                    <div class="mb-4">
                        <h3 class="text-xs font-black text-purple-600 uppercase mb-3 flex items-center gap-2"><span class="w-1 h-4 bg-purple-600 rounded-full"></span> IMMIGRATION STAMPS</h3>
                    </div>
                    <div id="stamps-container" class="space-y-4">
                        <?php if (empty($stamps)): ?>
                            <div class="p-4 bg-gray-50 rounded-xl border-2 border-gray-200 relative group">
                                <div class="grid grid-cols-2 gap-3 mt-2">
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase">Type</label>
                                        <select name="arrival_info[stamps][0][type]" class="w-full bg-white text-sm font-bold outline-none border-b border-gray-200 py-1">
                                            <option value="ARRIVAL">ARRIVAL</option>
                                            <option value="DEPARTURE">DEPARTURE</option>
                                            <option value="TRANSIT">TRANSIT</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase">Date</label>
                                        <input type="text" name="arrival_info[stamps][0][date]" placeholder="DD MMM YYYY" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase">Port of Entry/Exit</label>
                                        <input type="text" name="arrival_info[stamps][0][port]" placeholder="e.g., HARIDASPUR LCP" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase">Immigration Code</label>
                                        <input type="text" name="arrival_info[stamps][0][immigration_code]" placeholder="e.g., A2:2" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase">Mode of Travel</label>
                                        <input type="text" name="arrival_info[stamps][0][mode]" placeholder="AIR / ROAD / RAIL" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($stamps as $idx => $stamp): ?>
                                <div class="p-4 bg-gray-50 rounded-xl border-2 border-gray-200 relative group">
                                    <span class="absolute -top-3 left-4 <?php echo strtoupper($stamp['type'] ?? '') === 'ARRIVAL' ? 'bg-green-600' : 'bg-orange-600'; ?> text-white text-[9px] px-2 py-0.5 rounded-full uppercase font-black">
                                        <?php echo strtoupper($stamp['type'] ?? 'STAMP'); ?> #<?php echo $idx+1; ?>
                                    </span>
                                    <div class="grid grid-cols-2 gap-3 mt-2">
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-400 uppercase">Type</label>
                                            <select name="arrival_info[stamps][<?php echo $idx; ?>][type]" class="w-full bg-white text-sm font-bold outline-none border-b border-gray-200 py-1">
                                                <option value="ARRIVAL" <?php echo ($stamp['type'] ?? '') === 'ARRIVAL' ? 'selected' : ''; ?>>ARRIVAL</option>
                                                <option value="DEPARTURE" <?php echo ($stamp['type'] ?? '') === 'DEPARTURE' ? 'selected' : ''; ?>>DEPARTURE</option>
                                                <option value="TRANSIT" <?php echo ($stamp['type'] ?? '') === 'TRANSIT' ? 'selected' : ''; ?>>TRANSIT</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-400 uppercase">Date</label>
                                            <input type="text" name="arrival_info[stamps][<?php echo $idx; ?>][date]" value="<?php echo htmlspecialchars($stamp['date'] ?? ''); ?>" placeholder="DD MMM YYYY" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-[9px] font-bold text-gray-400 uppercase">Port of Entry/Exit</label>
                                            <input type="text" name="arrival_info[stamps][<?php echo $idx; ?>][port]" value="<?php echo htmlspecialchars($stamp['port'] ?? ''); ?>" placeholder="e.g., HARIDASPUR LCP" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-400 uppercase">Immigration Code</label>
                                            <input type="text" name="arrival_info[stamps][<?php echo $idx; ?>][immigration_code]" value="<?php echo htmlspecialchars($stamp['immigration_code'] ?? ''); ?>" placeholder="e.g., A2:2" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-400 uppercase">Mode of Travel</label>
                                            <input type="text" name="arrival_info[stamps][<?php echo $idx; ?>][mode]" value="<?php echo htmlspecialchars($stamp['mode'] ?? ''); ?>" placeholder="AIR / ROAD / RAIL" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="addStamp()" class="mt-5 w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-gray-400 font-bold text-xs hover:bg-gray-50 hover:border-purple-300 hover:text-purple-500 transition-all">+ Add New Stamp Record</button>
                <?php endif; ?>
                
                <?php if ($pageType === 'combined_page'): 
                    $visa = $aiResponse['visa_info'];
                ?>
                    <!-- Visa Information Section (for combined page) -->
                    <div class="form-section-card mb-5 p-4 rounded-xl border-2 border-blue-100 bg-blue-50/30">
                        <h3 class="text-xs font-black text-blue-700 uppercase mb-3 flex items-center gap-2"><span class="w-1 h-4 bg-blue-700 rounded-full"></span> VISA INFORMATION</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[9px] font-bold text-red-600 uppercase">Visa Number *</label>
                                <input type="text" name="visa_info[visa_number]" value="<?php echo htmlspecialchars($visa['visa_number'] ?? ''); ?>" class="w-full bg-white text-sm font-mono font-bold outline-none border-b border-red-300 py-1">
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[9px] font-bold text-red-600 uppercase">Visa Country Code *</label>
                                <input type="text" name="visa_info[visa_country_code]" value="<?php echo htmlspecialchars($visa['visa_country_code'] ?? ''); ?>" placeholder="e.g., IND, BGD, USA" class="w-full bg-white text-sm font-bold uppercase outline-none border-b border-red-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Passport Number</label>
                                <input type="text" name="visa_info[passport_number]" value="<?php echo htmlspecialchars($visa['passport_number'] ?? ''); ?>" class="w-full bg-white text-sm font-mono outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Visa Type</label>
                                <input type="text" name="visa_info[visa_type]" value="<?php echo htmlspecialchars($visa['visa_type'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Date of Issue</label>
                                <input type="text" name="visa_info[date_of_issue]" value="<?php echo htmlspecialchars($visa['date_of_issue'] ?? ''); ?>" placeholder="DD MMM YYYY" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Date of Expiry</label>
                                <input type="text" name="visa_info[date_of_expiry]" value="<?php echo htmlspecialchars($visa['date_of_expiry'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Maximum Stay</label>
                                <input type="text" name="visa_info[maximum_stay]" value="<?php echo htmlspecialchars($visa['maximum_stay'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Endorsement</label>
                                <input type="text" name="visa_info[endorsement]" value="<?php echo htmlspecialchars($visa['endorsement'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Entry Points</label>
                                <input type="text" name="visa_info[entry_points]" value="<?php echo htmlspecialchars($visa['entry_points'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Restrictions</label>
                                <input type="text" name="visa_info[restrictions]" value="<?php echo htmlspecialchars($visa['restrictions'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 font-bold uppercase">Registration Required</label>
                                <input type="text" name="visa_info[registration_required]" value="<?php echo htmlspecialchars($visa['registration_required'] ?? ''); ?>" class="w-full bg-white text-sm outline-none border-b border-gray-300 py-1">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        let rotation = 0;
        function rotateImg() {
            rotation = (rotation + 90) % 360;
            document.getElementById('passportImg').style.transform = 'rotate(' + rotation + 'deg)';
        }
        
        var stampCount = <?php echo isset($stamps) ? count($stamps) : 1; ?>;
        function addStamp() {
            var container = document.getElementById('stamps-container');
            var idx = stampCount++;
            var html = '<div class="p-4 bg-purple-50/50 rounded-xl border-2 border-purple-200 relative mt-4">' +
                '<span class="absolute -top-3 left-4 bg-purple-600 text-white text-[9px] px-2 py-0.5 rounded-full uppercase font-black">New Stamp</span>' +
                '<div class="grid grid-cols-2 gap-3 mt-2">' +
                    '<div>' +
                        '<label class="block text-[9px] font-bold text-gray-400 uppercase">Type</label>' +
                        '<select name="arrival_info[stamps][' + idx + '][type]" class="w-full bg-white text-sm font-bold outline-none border-b border-gray-200 py-1">' +
                            '<option value="ARRIVAL">ARRIVAL</option>' +
                            '<option value="DEPARTURE">DEPARTURE</option>' +
                            '<option value="TRANSIT">TRANSIT</option>' +
                        '</select>' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-[9px] font-bold text-gray-400 uppercase">Date</label>' +
                        '<input type="text" name="arrival_info[stamps][' + idx + '][date]" placeholder="DD MMM YYYY" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">' +
                    '</div>' +
                    '<div class="col-span-2">' +
                        '<label class="block text-[9px] font-bold text-gray-400 uppercase">Port of Entry/Exit</label>' +
                        '<input type="text" name="arrival_info[stamps][' + idx + '][port]" placeholder="e.g., HARIDASPUR LCP" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-[9px] font-bold text-gray-400 uppercase">Immigration Code</label>' +
                        '<input type="text" name="arrival_info[stamps][' + idx + '][immigration_code]" placeholder="e.g., A2:2" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-[9px] font-bold text-gray-400 uppercase">Mode of Travel</label>' +
                        '<input type="text" name="arrival_info[stamps][' + idx + '][mode]" placeholder="AIR / ROAD / RAIL" class="w-full bg-transparent text-sm outline-none border-b border-gray-200">' +
                    '</div>' +
                '</div>' +
            '</div>';
            container.insertAdjacentHTML('beforeend', html);
        }
        
        function changePageType() {
            var selectedType = document.getElementById('manualPageType').value;
            var url = new URL(window.location.href);
            url.searchParams.set('manual_type', selectedType);
            window.location.href = url.toString();
        }
        
        async function saveToDb() {
            var btn = document.getElementById('saveBtn');
            var formData = new FormData(document.getElementById('routerForm'));
            var output = {};
            
            for (var pair of formData.entries()) {
                var key = pair[0];
                var value = pair[1];
                var parts = key.split(/[\[\]]+/).filter(function(p) { return p; });
                var current = output;
                for (var i = 0; i < parts.length; i++) {
                    var p = parts[i];
                    if (i === parts.length - 1) {
                        current[p] = value;
                    } else {
                        current[p] = current[p] || (isNaN(parts[i+1]) ? {} : []);
                        current = current[p];
                    }
                }
            }
            
            var pageType = "<?php echo $pageType; ?>";
            
            // Validate visa country code for visa pages
            if (pageType === 'visa_page' || pageType === 'combined_page') {
                var visaCountry = output.visa_info && output.visa_info.visa_country_code;
                if (!visaCountry || visaCountry.trim() === '') {
                    if (!confirm('Visa Country Code is empty. This is important for records. Do you want to save anyway?')) {
                        btn.disabled = false;
                        btn.innerHTML = "💾 SAVE DATA";
                        return;
                    }
                }
            }
            
            btn.disabled = true;
            btn.innerHTML = '<span class="animate-pulse">⏳ SAVING...</span>';
            
            var fd = new FormData();
            fd.append('action', 'save_db');
            fd.append('json_data', JSON.stringify(output));
            fd.append('traveler_id', "<?php echo $traveler_id; ?>");
            fd.append('page_type', pageType);
            
            try {
                var res = await fetch(window.location.href, { method: 'POST', body: fd });
                var result = await res.json();
                if (result.success) {
                    btn.innerHTML = "✅ SAVED";
                    btn.className = "bg-black text-white px-8 py-3 rounded-2xl font-bold transition-all";
                    setTimeout(function() { window.history.back(); }, 1500);
                } else {
                    throw new Error('Save failed');
                }
            } catch (err) {
                alert('Error saving: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = "💾 SAVE DATA";
            }
        }
    </script>
</body>
</html>