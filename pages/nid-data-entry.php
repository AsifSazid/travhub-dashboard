<?php
// nid_processor.php - Main file for NID card processing

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
    
    $stmt = $conn->prepare("SELECT nid FROM traveler_profile WHERE traveler_id = ?");
    $stmt->bind_param("s", $tid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $history = json_decode($row['nid'], true) ?: [];
        $history[] = $newEntry;
        $finalJson = json_encode($history);
        $update = $conn->prepare("UPDATE traveler_profile SET nid = ? WHERE traveler_id = ?");
        $update->bind_param("ss", $finalJson, $tid);
        $success = $update->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO traveler_profile (traveler_id, nid) VALUES (?, ?)");
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
$tempLocalFile = tempnam(sys_get_temp_dir(), 'nid_');
$omv->get_file($filePath, $tempLocalFile);
$mimeType = (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'png') ? 'image/png' : 'image/jpeg';
$imageData = base64_encode(file_get_contents($tempLocalFile));
unlink($tempLocalFile);

// Enhanced prompt for Bangladeshi NID card (both front and back)
$prompt = "Analyze this Bangladeshi National ID Card image carefully. Determine if it's the FRONT side or BACK side of the NID card.

IMPORTANT INSTRUCTIONS:
1. Look for BOTH English and Bengali text on the card
2. Extract ALL information visible

CLASSIFY THE PAGE TYPE:
- **nid_front** - Front side of NID card containing personal information, photo, and NID number
- **nid_back** - Back side of NID card containing address, blood group, place of birth, issue date, and MRZ code

Return ONLY a JSON object with this exact structure:

For nid_front:
{
    \"page_type\": \"nid_front\",
    \"nid_info\": {
        \"nid_number\": \"\",
        \"full_name_en\": \"\",
        \"full_name_bn\": \"\",
        \"father_name_en\": \"\",
        \"father_name_bn\": \"\",
        \"mother_name_en\": \"\",
        \"mother_name_bn\": \"\",
        \"date_of_birth\": \"\",
        \"date_of_birth_bn\": \"\",
        \"photo_present\": true
    }
}

For nid_back:
{
    \"page_type\": \"nid_back\",
    \"nid_info\": {
        \"permanent_address_en\": \"\",
        \"permanent_address_bn\": \"\",
        \"blood_group\": \"\",
        \"place_of_birth_en\": \"\",
        \"place_of_birth_bn\": \"\",
        \"issue_date\": \"\",
        \"mrz_line_1\": \"\",
        \"mrz_line_2\": \"\",
        \"mrz_line_3\": \"\"
    }
}

Extract ALL text accurately. For Bengali text, include both the original Bengali and English translation where available. Return ONLY valid JSON.";

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
if ($manualPageType && in_array($manualPageType, ['nid_front', 'nid_back'])) {
    $pageType = $manualPageType;
} else {
    $pageType = isset($aiResponse['page_type']) ? $aiResponse['page_type'] : null;
    
    // Fallback detection based on content
    if (!$pageType) {
        $nidInfo = $aiResponse['nid_info'] ?? [];
        if (isset($nidInfo['nid_number']) && !empty($nidInfo['nid_number'])) {
            $pageType = 'nid_front';
        } elseif (isset($nidInfo['mrz_line_1']) && !empty($nidInfo['mrz_line_1'])) {
            $pageType = 'nid_back';
        } else {
            // Default based on filename
            $fileName = basename($filePath);
            if (stripos($fileName, 'back') !== false || stripos($fileName, 'reverse') !== false) {
                $pageType = 'nid_back';
            } else {
                $pageType = 'nid_front';
            }
        }
    }
}

// Ensure proper structure based on page type
if ($pageType === 'nid_front') {
    if (!isset($aiResponse['nid_info'])) {
        $aiResponse['nid_info'] = [];
    }
    
    $defaultFront = [
        'nid_number' => '',
        'full_name_en' => '',
        'full_name_bn' => '',
        'father_name_en' => '',
        'father_name_bn' => '',
        'mother_name_en' => '',
        'mother_name_bn' => '',
        'date_of_birth' => '',
        'date_of_birth_bn' => '',
        'photo_present' => true
    ];
    
    $aiResponse['nid_info'] = array_merge($defaultFront, $aiResponse['nid_info']);
} else {
    if (!isset($aiResponse['nid_info'])) {
        $aiResponse['nid_info'] = [];
    }
    
    $defaultBack = [
        'permanent_address_en' => '',
        'permanent_address_bn' => '',
        'blood_group' => '',
        'place_of_birth_en' => '',
        'place_of_birth_bn' => '',
        'issue_date' => '',
        'mrz_line_1' => '',
        'mrz_line_2' => '',
        'mrz_line_3' => ''
    ];
    
    $aiResponse['nid_info'] = array_merge($defaultBack, $aiResponse['nid_info']);
}

$nidInfo = $aiResponse['nid_info'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bangladeshi NID Card Verification - <?php echo $pageType === 'nid_front' ? 'Front Side' : 'Back Side'; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sticky-col { position: sticky; top: 1rem; }
        .img-box img { transition: transform 0.3s ease; }
        .form-section-card { transition: all 0.2s ease; }
        .form-section-card:hover { border-color: #10b981; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        input:focus, textarea:focus, select:focus { border-color: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,0.2); outline: none; }
        .page-type-badge {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        }
        .select-page-type {
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
        }
        .bengali-text {
            font-family: 'Noto Sans Bengali', 'SolaimanLipi', 'Siyam Rupali', sans-serif;
        }
        .mrz-text {
            font-family: 'Courier New', 'OCR-B', monospace;
            letter-spacing: 1px;
        }
        .info-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        hr {
            margin: 1rem 0;
            border-color: #e5e7eb;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-full mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden grid lg:grid-cols-12 min-h-screen">
        
        <!-- Left Column: Image -->
        <div class="lg:col-span-7 p-6 bg-gradient-to-br from-gray-900 to-gray-800 overflow-y-auto">
            <div class="sticky-col">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex gap-2">
                        <span class="text-white text-xs font-bold uppercase tracking-widest bg-green-600 px-3 py-1 rounded-full">
                            <i class="fas fa-id-card mr-1"></i> NID Card
                        </span>
                        <span class="page-type-badge text-white text-xs font-bold uppercase px-3 py-1 rounded-full">
                            <?php echo $pageType === 'nid_front' ? 'FRONT SIDE' : 'BACK SIDE'; ?>
                        </span>
                    </div>
                    <button onclick="rotateImg()" class="text-white bg-gray-700 px-3 py-1 rounded-lg text-xs hover:bg-gray-600 transition">
                        <i class="fas fa-rotate-right mr-1"></i> Rotate
                    </button>
                </div>
                
                <!-- Manual Page Type Selector -->
                <div class="mb-4 p-3 bg-gray-800 rounded-xl">
                    <label class="text-white text-xs font-bold uppercase block mb-2">
                        <i class="fas fa-exchange-alt mr-1"></i> Manual Side Selection (if AI detection is incorrect)
                    </label>
                    <div class="flex gap-2">
                        <select id="manualPageType" class="flex-1 bg-gray-700 text-white text-sm rounded-lg px-3 py-2 border border-gray-600 focus:border-green-500">
                            <option value="nid_front" <?php echo $pageType === 'nid_front' ? 'selected' : ''; ?>>
                                <i class="fas fa-user"></i> 🪪 Front Side (Personal Info)
                            </option>
                            <option value="nid_back" <?php echo $pageType === 'nid_back' ? 'selected' : ''; ?>>
                                <i class="fas fa-address-card"></i> 🏠 Back Side (Address & MRZ)
                            </option>
                        </select>
                        <button onclick="changePageType()" class="select-page-type text-white px-4 py-2 rounded-lg text-sm font-bold hover:opacity-90 transition">
                            <i class="fas fa-check mr-1"></i> Apply Change
                        </button>
                    </div>
                    <p class="text-gray-400 text-[10px] mt-2">
                        <i class="fas fa-info-circle mr-1"></i> Use this if AI misidentified the card side. Page will reload with correct form.
                    </p>
                </div>
                
                <div id="imgWrapper" class="img-box rounded-2xl overflow-hidden shadow-2xl bg-black flex items-center justify-center">
                    <img id="nidImg" src="data:<?php echo $mimeType; ?>;base64,<?php echo $imageData; ?>" class="w-full h-auto" alt="NID Card Image">
                </div>
            </div>
        </div>

        <!-- Right Column: Form -->
        <div class="lg:col-span-5 p-8 overflow-y-auto bg-white">
            <div class="flex justify-between items-center mb-6 pb-4 border-b">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 uppercase italic flex items-center gap-2">
                        <i class="fas fa-id-card text-green-600"></i>
                        NID Verification
                    </h2>
                    <p class="text-[10px] font-bold text-green-600 uppercase mt-1">
                        <i class="fas fa-user mr-1"></i> Traveler ID: <?php echo htmlspecialchars($traveler_id); ?>
                    </p>
                </div>
                <button onclick="saveToDb()" id="saveBtn" class="bg-green-600 text-white px-8 py-3 rounded-2xl font-bold hover:bg-green-700 shadow-xl transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i> SAVE DATA
                </button>
            </div>
            
            <form id="nidForm">
                <input type="hidden" name="page_type" value="<?php echo $pageType; ?>">
                
                <?php if ($pageType === 'nid_front'): ?>
                    <!-- Front Side - Personal Information -->
                    <div class="info-card form-section-card mb-5 p-5 rounded-xl border border-green-200">
                        <h3 class="text-sm font-black text-green-700 uppercase mb-4 flex items-center gap-2">
                            <i class="fas fa-user-circle text-lg"></i>
                            <span>Personal Information</span>
                        </h3>
                        
                        <!-- NID Number -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                                <i class="fas fa-hashtag text-green-600 mr-1"></i> NID Number *
                            </label>
                            <input type="text" name="nid_info[nid_number]" value="<?php echo htmlspecialchars($nidInfo['nid_number'] ?? ''); ?>" 
                                placeholder="e.g., 6001484622"
                                class="w-full bg-white text-lg font-mono font-bold text-gray-800 outline-none border-b-2 border-green-300 py-2 focus:border-green-600">
                            <p class="text-[10px] text-gray-400 mt-1">10-17 digit number on the front of the card</p>
                        </div>
                        
                        <!-- Full Name (English) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                                <i class="fas fa-signature text-green-600 mr-1"></i> Full Name (English)
                            </label>
                            <input type="text" name="nid_info[full_name_en]" value="<?php echo htmlspecialchars($nidInfo['full_name_en'] ?? ''); ?>" 
                                placeholder="e.g., ESTIAK SIDDIKI"
                                class="w-full bg-white text-base font-semibold text-gray-800 outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <!-- Full Name (Bengali) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text">
                                <i class="fas fa-language text-green-600 mr-1"></i> সম্পূর্ণ নাম (বাংলা)
                            </label>
                            <input type="text" name="nid_info[full_name_bn]" value="<?php echo htmlspecialchars($nidInfo['full_name_bn'] ?? ''); ?>" 
                                placeholder="e.g., ইসতিয়াক সিদ্দিকী"
                                class="w-full bg-white text-base bengali-text outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <hr>
                        
                        <!-- Father's Name (English) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                                <i class="fas fa-male text-green-600 mr-1"></i> Father's Name (English)
                            </label>
                            <input type="text" name="nid_info[father_name_en]" value="<?php echo htmlspecialchars($nidInfo['father_name_en'] ?? ''); ?>" 
                                placeholder="e.g., MAHILAR RAHMAN SIDDIKI"
                                class="w-full bg-white text-base outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <!-- Father's Name (Bengali) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text">
                                <i class="fas fa-male text-green-600 mr-1"></i> পিতার নাম (বাংলা)
                            </label>
                            <input type="text" name="nid_info[father_name_bn]" value="<?php echo htmlspecialchars($nidInfo['father_name_bn'] ?? ''); ?>" 
                                placeholder="e.g., মহিলার রহমান সিদ্দিকী"
                                class="w-full bg-white text-base bengali-text outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <!-- Mother's Name (English) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                                <i class="fas fa-female text-green-600 mr-1"></i> Mother's Name (English)
                            </label>
                            <input type="text" name="nid_info[mother_name_en]" value="<?php echo htmlspecialchars($nidInfo['mother_name_en'] ?? ''); ?>" 
                                placeholder="e.g., SHAHANAJ SIDDIKI"
                                class="w-full bg-white text-base outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <!-- Mother's Name (Bengali) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text">
                                <i class="fas fa-female text-green-600 mr-1"></i> মাতার নাম (বাংলা)
                            </label>
                            <input type="text" name="nid_info[mother_name_bn]" value="<?php echo htmlspecialchars($nidInfo['mother_name_bn'] ?? ''); ?>" 
                                placeholder="e.g., শাহানাজ সিদ্দিকী"
                                class="w-full bg-white text-base bengali-text outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <hr>
                        
                        <!-- Date of Birth (English) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                                <i class="fas fa-calendar-alt text-green-600 mr-1"></i> Date of Birth (English)
                            </label>
                            <input type="text" name="nid_info[date_of_birth]" value="<?php echo htmlspecialchars($nidInfo['date_of_birth'] ?? ''); ?>" 
                                placeholder="e.g., 31 Dec 1995 or 31-12-1995"
                                class="w-full bg-white text-base outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <!-- Date of Birth (Bengali) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text">
                                <i class="fas fa-calendar-alt text-green-600 mr-1"></i> জন্ম তারিখ (বাংলা)
                            </label>
                            <input type="text" name="nid_info[date_of_birth_bn]" value="<?php echo htmlspecialchars($nidInfo['date_of_birth_bn'] ?? ''); ?>" 
                                placeholder="e.g., ৩১ ডিসেম্বর ১৯৯৫"
                                class="w-full bg-white text-base bengali-text outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <!-- Photo Present -->
                        <div class="mb-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="nid_info[photo_present]" value="true" 
                                    <?php echo (isset($nidInfo['photo_present']) && $nidInfo['photo_present'] === true) ? 'checked' : ''; ?>
                                    class="w-4 h-4 text-green-600 focus:ring-green-500">
                                <span class="text-xs font-bold text-gray-600 uppercase">
                                    <i class="fas fa-camera text-green-600 mr-1"></i> Photo present on card
                                </span>
                            </label>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Back Side - Address & MRZ Information -->
                    <div class="info-card form-section-card mb-5 p-5 rounded-xl border border-green-200">
                        <h3 class="text-sm font-black text-green-700 uppercase mb-4 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-lg"></i>
                            <span>Address Information</span>
                        </h3>
                        
                        <!-- Permanent Address (English) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                                <i class="fas fa-home text-green-600 mr-1"></i> Permanent Address (English)
                            </label>
                            <textarea name="nid_info[permanent_address_en]" rows="3" 
                                placeholder="e.g., House-9, Road-7, Sector-6, Mirpur, Dhaka-1216"
                                class="w-full bg-white text-sm outline-none border-b border-gray-300 py-2 focus:border-green-500"><?php echo htmlspecialchars($nidInfo['permanent_address_en'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Permanent Address (Bengali) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text">
                                <i class="fas fa-home text-green-600 mr-1"></i> স্থায়ী ঠিকানা (বাংলা)
                            </label>
                            <textarea name="nid_info[permanent_address_bn]" rows="3" 
                                placeholder="বাংলায় ঠিকানা লিখুন"
                                class="w-full bg-white text-sm bengali-text outline-none border-b border-gray-300 py-2 focus:border-green-500"><?php echo htmlspecialchars($nidInfo['permanent_address_bn'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr>
                        
                        <!-- Blood Group -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                                <i class="fas fa-tint text-red-500 mr-1"></i> Blood Group
                            </label>
                            <select name="nid_info[blood_group]" class="w-full bg-white text-base outline-none border-b border-gray-300 py-2 focus:border-green-500">
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo ($nidInfo['blood_group'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($nidInfo['blood_group'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($nidInfo['blood_group'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($nidInfo['blood_group'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo ($nidInfo['blood_group'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($nidInfo['blood_group'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo ($nidInfo['blood_group'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($nidInfo['blood_group'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                        
                        <!-- Place of Birth (English) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                                <i class="fas fa-birthday-cake text-green-600 mr-1"></i> Place of Birth (English)
                            </label>
                            <input type="text" name="nid_info[place_of_birth_en]" value="<?php echo htmlspecialchars($nidInfo['place_of_birth_en'] ?? ''); ?>" 
                                placeholder="e.g., TANGAIL"
                                class="w-full bg-white text-base outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <!-- Place of Birth (Bengali) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text">
                                <i class="fas fa-birthday-cake text-green-600 mr-1"></i> জন্মস্থান (বাংলা)
                            </label>
                            <input type="text" name="nid_info[place_of_birth_bn]" value="<?php echo htmlspecialchars($nidInfo['place_of_birth_bn'] ?? ''); ?>" 
                                placeholder="e.g., টাঙ্গাইল"
                                class="w-full bg-white text-base bengali-text outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                        
                        <!-- Issue Date -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                                <i class="fas fa-calendar-check text-green-600 mr-1"></i> Issue Date
                            </label>
                            <input type="text" name="nid_info[issue_date]" value="<?php echo htmlspecialchars($nidInfo['issue_date'] ?? ''); ?>" 
                                placeholder="e.g., 31 Oct 2015"
                                class="w-full bg-white text-base outline-none border-b border-gray-300 py-2 focus:border-green-500">
                        </div>
                    </div>
                    
                    <!-- MRZ Section -->
                    <div class="form-section-card mb-5 p-5 rounded-xl border border-green-200 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="text-sm font-black text-green-700 uppercase mb-4 flex items-center gap-2">
                            <i class="fas fa-barcode text-lg"></i>
                            <span>Machine Readable Zone (MRZ)</span>
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">MRZ Line 1</label>
                                <input type="text" name="nid_info[mrz_line_1]" value="<?php echo htmlspecialchars($nidInfo['mrz_line_1'] ?? ''); ?>" 
                                    class="w-full bg-white text-xs mrz-text outline-none border-b border-gray-300 py-2 focus:border-green-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">MRZ Line 2</label>
                                <input type="text" name="nid_info[mrz_line_2]" value="<?php echo htmlspecialchars($nidInfo['mrz_line_2'] ?? ''); ?>" 
                                    class="w-full bg-white text-xs mrz-text outline-none border-b border-gray-300 py-2 focus:border-green-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">MRZ Line 3</label>
                                <input type="text" name="nid_info[mrz_line_3]" value="<?php echo htmlspecialchars($nidInfo['mrz_line_3'] ?? ''); ?>" 
                                    class="w-full bg-white text-xs mrz-text outline-none border-b border-gray-300 py-2 focus:border-green-500 font-mono">
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-3">
                            <i class="fas fa-info-circle mr-1"></i> MRZ contains encoded personal information from the NID card
                        </p>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        let rotation = 0;
        function rotateImg() {
            rotation = (rotation + 90) % 360;
            document.getElementById('nidImg').style.transform = 'rotate(' + rotation + 'deg)';
        }
        
        function changePageType() {
            var selectedType = document.getElementById('manualPageType').value;
            var url = new URL(window.location.href);
            url.searchParams.set('manual_type', selectedType);
            window.location.href = url.toString();
        }
        
        async function saveToDb() {
            var btn = document.getElementById('saveBtn');
            var formData = new FormData(document.getElementById('nidForm'));
            var output = {};
            
            for (var pair of formData.entries()) {
                var key = pair[0];
                var value = pair[1];
                
                // Handle checkbox for photo_present
                if (key === 'nid_info[photo_present]') {
                    value = true;
                }
                
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
            
            // Validate NID number for front side
            if (pageType === 'nid_front') {
                var nidNumber = output.nid_info && output.nid_info.nid_number;
                if (!nidNumber || nidNumber.trim() === '') {
                    if (!confirm('NID Number is empty. This is important for records. Do you want to save anyway?')) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save"></i> SAVE DATA';
                        return;
                    }
                }
            }
            
            btn.disabled = true;
            btn.innerHTML = '<span class="animate-pulse"><i class="fas fa-spinner fa-spin"></i> SAVING...</span>';
            
            var fd = new FormData();
            fd.append('action', 'save_db');
            fd.append('json_data', JSON.stringify(output));
            fd.append('traveler_id', "<?php echo $traveler_id; ?>");
            fd.append('page_type', pageType);
            
            try {
                var res = await fetch(window.location.href, { method: 'POST', body: fd });
                var result = await res.json();
                if (result.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> SAVED!';
                    btn.className = "bg-black text-white px-8 py-3 rounded-2xl font-bold transition-all";
                    setTimeout(function() { window.history.back(); }, 1500);
                } else {
                    throw new Error('Save failed');
                }
            } catch (err) {
                alert('Error saving: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> SAVE DATA';
            }
        }
        
        // Auto-format NID number as user types
        const nidInput = document.querySelector('input[name="nid_info[nid_number]"]');
        if (nidInput) {
            nidInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 3 && value.length <= 6) {
                    value = value.slice(0, 3) + ' ' + value.slice(3);
                } else if (value.length > 6 && value.length <= 10) {
                    value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6);
                } else if (value.length > 10) {
                    value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6, 10);
                }
                this.value = value;
            });
        }
    </script>
</body>
</html>