<?php
// nid-data-entry.php - Bangladeshi NID Card Processing (Unified Design)

session_start();

require_once '../server/live_storage.php';
require_once '../server/db_connection.php';

$filePath = $_GET['path'] ?? null;
if (!$filePath) die("No file path provided.");

$pathParts = explode('/', $filePath);
$traveler_id = $_GET['tid'] ?? null;

$GEMINI_API_KEY = trim(file_get_contents('../gemini-apikey.txt'));
$apiKey = $GEMINI_API_KEY;
$model = 'gemini-2.0-flash-lite';

// --- HANDLE SAVE DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_db') {
    header('Content-Type: application/json');
    
    $newEntry = json_decode($_POST['json_data'], true);
    $tid = $_POST['traveler_id'];
    $pageType = $_POST['page_type'] ?? 'unknown';
    
    $newEntry['_metadata'] = [
        'saved_at' => date('Y-m-d H:i:s'),
        'page_type' => $pageType,
        'source_file' => $filePath ?? 'unknown',
        'created_by' => $_SESSION['user_name']
    ];
    
    try {
        $stmt = $pdo->prepare("SELECT nid_info FROM travelers WHERE sys_id = ?");
        $stmt->execute([$tid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $history = json_decode($row['nid_info'] ?? '', true) ?: [];
            $history[] = $newEntry;
            $finalJson = json_encode($history);
            $update = $pdo->prepare("UPDATE travelers SET nid_info = ? WHERE sys_id = ?");
            $success = $update->execute([$finalJson, $tid]);
        } else {
            $jsonStr = json_encode([$newEntry]);
            $insert = $pdo->prepare("INSERT INTO travelers (sys_id, nid_info) VALUES (?, ?)");
            $success = $insert->execute([$tid, $jsonStr]);
        }
        
        echo json_encode(['success' => $success]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --- HANDLE MANUAL PAGE TYPE SELECTION ---
$manualPageType = $_POST['manual_page_type'] ?? $_GET['manual_type'] ?? null;

// --- SMB & AI PROCESSING ---
$omv = new OMV_SMB_Manager();
$tempLocalFile = tempnam(sys_get_temp_dir(), 'nid_');
$omv->get_file($filePath, $tempLocalFile);
$mimeType = (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'png') ? 'image/png' : 'image/jpeg';
$imageData = base64_encode(file_get_contents($tempLocalFile));
unlink($tempLocalFile);

$prompt = "Analyze this Bangladeshi National ID Card image carefully. Determine if it's the FRONT side or BACK side.

Return ONLY JSON:
For nid_front: {\"page_type\":\"nid_front\",\"nid_info\":{\"nid_number\":\"\",\"full_name_en\":\"\",\"full_name_bn\":\"\",\"father_name_en\":\"\",\"father_name_bn\":\"\",\"mother_name_en\":\"\",\"mother_name_bn\":\"\",\"date_of_birth\":\"\",\"date_of_birth_bn\":\"\",\"photo_present\":true}}
For nid_back: {\"page_type\":\"nid_back\",\"nid_info\":{\"permanent_address_en\":\"\",\"permanent_address_bn\":\"\",\"blood_group\":\"\",\"place_of_birth_en\":\"\",\"place_of_birth_bn\":\"\",\"issue_date\":\"\",\"mrz_line_1\":\"\",\"mrz_line_2\":\"\",\"mrz_line_3\":\"\"}}";

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

if ($manualPageType && in_array($manualPageType, ['nid_front', 'nid_back'])) {
    $pageType = $manualPageType;
} else {
    $pageType = $aiResponse['page_type'] ?? null;
    if (!$pageType) {
        $fileName = basename($filePath);
        if (stripos($fileName, 'back') !== false || stripos($fileName, 'reverse') !== false) {
            $pageType = 'nid_back';
        } else {
            $pageType = 'nid_front';
        }
    }
}

if ($pageType === 'nid_front') {
    if (!isset($aiResponse['nid_info'])) $aiResponse['nid_info'] = [];
    $defaultFront = ['nid_number'=>'','full_name_en'=>'','full_name_bn'=>'','father_name_en'=>'','father_name_bn'=>'','mother_name_en'=>'','mother_name_bn'=>'','date_of_birth'=>'','date_of_birth_bn'=>'','photo_present'=>true];
    $aiResponse['nid_info'] = array_merge($defaultFront, $aiResponse['nid_info']);
} else {
    if (!isset($aiResponse['nid_info'])) $aiResponse['nid_info'] = [];
    $defaultBack = ['permanent_address_en'=>'','permanent_address_bn'=>'','blood_group'=>'','place_of_birth_en'=>'','place_of_birth_bn'=>'','issue_date'=>'','mrz_line_1'=>'','mrz_line_2'=>'','mrz_line_3'=>''];
    $aiResponse['nid_info'] = array_merge($defaultBack, $aiResponse['nid_info']);
}
$nidInfo = $aiResponse['nid_info'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NID Card Verification | TravelDocs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Minimal custom CSS - only for effects Tailwind can't handle */
        .rotate-transition { transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1); }
        .btn-save-gradient { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        .btn-save-gradient:hover { background: linear-gradient(135deg, #047857 0%, #059669 100%); transform: translateY(-1px); }
        .badge-gradient { background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); }
        .selector-gradient { background: linear-gradient(135deg, #f97316 0%, #f59e0b 100%); }
        .info-card-bg { background: linear-gradient(115deg, #f0fdf4 0%, #dcfce7 100%); }
        input:focus, textarea:focus, select:focus { box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); outline: none; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4 md:p-6 font-sans">
    <div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden grid lg:grid-cols-12 min-h-[85vh]">
        
        <!-- LEFT COLUMN: Image Section (sticky) -->
        <div class="lg:col-span-7 bg-gradient-to-br from-gray-900 to-gray-800 p-6 overflow-y-auto">
            <div class="sticky top-4 space-y-4">
                <!-- Badges -->
                <div class="flex justify-between items-center">
                    <div class="flex gap-2">
                        <span class="bg-emerald-600 text-white text-xs font-bold uppercase tracking-wider px-3 py-1.5 rounded-full"><i class="fas fa-id-card mr-1"></i> NID CARD</span>
                        <span class="badge-gradient text-white text-xs font-bold uppercase px-3 py-1.5 rounded-full"><?php echo $pageType === 'nid_front' ? 'FRONT SIDE' : 'BACK SIDE'; ?></span>
                    </div>
                    <button onclick="rotateImg()" class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"><i class="fas fa-rotate-right mr-1"></i> Rotate</button>
                </div>
                
                <!-- Manual Page Type Override -->
                <div class="bg-gray-800/80 backdrop-blur-sm rounded-xl p-3 border border-gray-700">
                    <label class="text-white text-xs font-bold uppercase block mb-2"><i class="fas fa-exchange-alt mr-1 text-amber-400"></i> Manual Side Selection</label>
                    <div class="flex gap-2">
                        <select id="manualPageType" class="flex-1 bg-gray-700 text-white text-sm rounded-lg px-3 py-2 border border-gray-600 focus:border-emerald-500">
                            <option value="nid_front" <?php echo $pageType === 'nid_front' ? 'selected' : ''; ?>>🪪 Front Side (Personal Info)</option>
                            <option value="nid_back" <?php echo $pageType === 'nid_back' ? 'selected' : ''; ?>>🏠 Back Side (Address & MRZ)</option>
                        </select>
                        <button onclick="changePageType()" class="selector-gradient text-white px-5 py-2 rounded-lg text-sm font-bold hover:opacity-90 transition-all"><i class="fas fa-check mr-1"></i> Apply</button>
                    </div>
                    <p class="text-gray-400 text-[10px] mt-2"><i class="fas fa-info-circle mr-1"></i> Use if AI misidentified the card side. Page will reload.</p>
                </div>
                
                <!-- Image Display -->
                <div class="bg-black rounded-xl overflow-hidden shadow-xl flex items-center justify-center p-2">
                    <img id="nidImg" src="data:<?php echo $mimeType; ?>;base64,<?php echo $imageData; ?>" class="w-full h-auto rounded-lg rotate-transition" alt="NID Card">
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Form Section -->
        <div class="lg:col-span-5 p-6 md:p-8 overflow-y-auto bg-white">
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 flex items-center gap-2"><i class="fas fa-id-card text-emerald-600"></i> NID Verification</h2>
                    <p class="text-xs font-semibold text-emerald-600 mt-1"><i class="fas fa-user mr-1"></i> Traveler ID: <?php echo htmlspecialchars($traveler_id); ?></p>
                </div>
                <button onclick="saveToDb()" id="saveBtn" class="btn-save-gradient text-white px-6 py-3 rounded-xl font-bold shadow-lg transition-all flex items-center gap-2"><i class="fas fa-save"></i> SAVE DATA</button>
            </div>
            
            <form id="nidForm">
                <input type="hidden" name="page_type" value="<?php echo $pageType; ?>">
                
                <?php if ($pageType === 'nid_front'): ?>
                    <!-- Front Side Form -->
                    <div class="info-card-bg rounded-xl p-5 mb-5 border border-emerald-200">
                        <h3 class="text-sm font-black text-emerald-700 uppercase mb-4 flex items-center gap-2"><i class="fas fa-user-circle"></i> Personal Information</h3>
                        <div class="space-y-4">
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1"><i class="fas fa-hashtag text-emerald-600"></i> NID Number *</label><input type="text" name="nid_info[nid_number]" value="<?php echo htmlspecialchars($nidInfo['nid_number'] ?? ''); ?>" class="w-full border-b-2 border-emerald-200 py-2 focus:border-emerald-500 outline-none text-lg font-mono font-bold"></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1"><i class="fas fa-signature"></i> Full Name (English)</label><input type="text" name="nid_info[full_name_en]" value="<?php echo htmlspecialchars($nidInfo['full_name_en'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none"></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text"><i class="fas fa-language"></i> সম্পূর্ণ নাম (বাংলা)</label><input type="text" name="nid_info[full_name_bn]" value="<?php echo htmlspecialchars($nidInfo['full_name_bn'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none bengali-text"></div>
                            <hr class="border-emerald-100">
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1"><i class="fas fa-male"></i> Father's Name (English)</label><input type="text" name="nid_info[father_name_en]" value="<?php echo htmlspecialchars($nidInfo['father_name_en'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none"></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text"><i class="fas fa-male"></i> পিতার নাম (বাংলা)</label><input type="text" name="nid_info[father_name_bn]" value="<?php echo htmlspecialchars($nidInfo['father_name_bn'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none bengali-text"></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1"><i class="fas fa-female"></i> Mother's Name (English)</label><input type="text" name="nid_info[mother_name_en]" value="<?php echo htmlspecialchars($nidInfo['mother_name_en'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none"></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text"><i class="fas fa-female"></i> মাতার নাম (বাংলা)</label><input type="text" name="nid_info[mother_name_bn]" value="<?php echo htmlspecialchars($nidInfo['mother_name_bn'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none bengali-text"></div>
                            <hr class="border-emerald-100">
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1"><i class="fas fa-calendar-alt"></i> Date of Birth (English)</label><input type="text" name="nid_info[date_of_birth]" value="<?php echo htmlspecialchars($nidInfo['date_of_birth'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none"></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text"><i class="fas fa-calendar-alt"></i> জন্ম তারিখ (বাংলা)</label><input type="text" name="nid_info[date_of_birth_bn]" value="<?php echo htmlspecialchars($nidInfo['date_of_birth_bn'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none bengali-text"></div>
                            <div><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="nid_info[photo_present]" value="true" <?php echo (isset($nidInfo['photo_present']) && $nidInfo['photo_present'] === true) ? 'checked' : ''; ?> class="w-4 h-4 text-emerald-600 rounded"><span class="text-xs font-bold text-gray-600"><i class="fas fa-camera text-emerald-600 mr-1"></i> Photo present on card</span></label></div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Back Side Form -->
                    <div class="info-card-bg rounded-xl p-5 mb-5 border border-emerald-200">
                        <h3 class="text-sm font-black text-emerald-700 uppercase mb-4 flex items-center gap-2"><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                        <div class="space-y-4">
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1"><i class="fas fa-home"></i> Permanent Address (English)</label><textarea name="nid_info[permanent_address_en]" rows="2" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none"><?php echo htmlspecialchars($nidInfo['permanent_address_en'] ?? ''); ?></textarea></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text"><i class="fas fa-home"></i> স্থায়ী ঠিকানা (বাংলা)</label><textarea name="nid_info[permanent_address_bn]" rows="2" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none bengali-text"><?php echo htmlspecialchars($nidInfo['permanent_address_bn'] ?? ''); ?></textarea></div>
                            <hr>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1"><i class="fas fa-tint text-red-500"></i> Blood Group</label><select name="nid_info[blood_group]" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none"><option value="">Select</option><option value="A+" <?php echo ($nidInfo['blood_group'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option><option value="A-" <?php echo ($nidInfo['blood_group'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option><option value="B+" <?php echo ($nidInfo['blood_group'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option><option value="B-" <?php echo ($nidInfo['blood_group'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option><option value="O+" <?php echo ($nidInfo['blood_group'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option><option value="O-" <?php echo ($nidInfo['blood_group'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option></select></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1"><i class="fas fa-birthday-cake"></i> Place of Birth (English)</label><input type="text" name="nid_info[place_of_birth_en]" value="<?php echo htmlspecialchars($nidInfo['place_of_birth_en'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none"></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1 bengali-text"><i class="fas fa-birthday-cake"></i> জন্মস্থান (বাংলা)</label><input type="text" name="nid_info[place_of_birth_bn]" value="<?php echo htmlspecialchars($nidInfo['place_of_birth_bn'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none bengali-text"></div>
                            <div><label class="block text-xs font-bold text-gray-600 uppercase mb-1"><i class="fas fa-calendar-check"></i> Issue Date</label><input type="text" name="nid_info[issue_date]" value="<?php echo htmlspecialchars($nidInfo['issue_date'] ?? ''); ?>" class="w-full border-b border-gray-300 py-2 focus:border-emerald-500 outline-none"></div>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                        <h3 class="text-sm font-black text-emerald-700 uppercase mb-3"><i class="fas fa-barcode mr-2"></i> Machine Readable Zone (MRZ)</h3>
                        <div class="space-y-3"><input type="text" name="nid_info[mrz_line_1]" value="<?php echo htmlspecialchars($nidInfo['mrz_line_1'] ?? ''); ?>" class="w-full font-mono text-xs border-b border-gray-300 py-2" placeholder="MRZ Line 1"><input type="text" name="nid_info[mrz_line_2]" value="<?php echo htmlspecialchars($nidInfo['mrz_line_2'] ?? ''); ?>" class="w-full font-mono text-xs border-b border-gray-300 py-2" placeholder="MRZ Line 2"><input type="text" name="nid_info[mrz_line_3]" value="<?php echo htmlspecialchars($nidInfo['mrz_line_3'] ?? ''); ?>" class="w-full font-mono text-xs border-b border-gray-300 py-2" placeholder="MRZ Line 3"></div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        let rotation = 0;
        function rotateImg() { rotation = (rotation + 90) % 360; document.getElementById('nidImg').style.transform = 'rotate(' + rotation + 'deg)'; }
        function changePageType() { var url = new URL(window.location.href); url.searchParams.set('manual_type', document.getElementById('manualPageType').value); window.location.href = url.toString(); }
        async function saveToDb() {
            var btn = document.getElementById('saveBtn'), formData = new FormData(document.getElementById('nidForm')), output = {};
            for (var pair of formData.entries()) { var key = pair[0], value = pair[1], parts = key.split(/[\[\]]+/).filter(p=>p), cur = output; for (var i=0; i<parts.length; i++) { var p = parts[i]; if (i === parts.length-1) cur[p] = value; else cur[p] = cur[p] || (isNaN(parts[i+1]) ? {} : []); cur = cur[p]; } }
            var pageType = "<?php echo $pageType; ?>";
            if (pageType === 'nid_front' && (!output.nid_info?.nid_number || output.nid_info.nid_number.trim() === '')) { if (!confirm('NID Number is empty. Save anyway?')) return; }
            btn.disabled = true; btn.innerHTML = '<span class="animate-pulse"><i class="fas fa-spinner fa-spin"></i> SAVING...</span>';
            var fd = new FormData(); fd.append('action','save_db'); fd.append('json_data',JSON.stringify(output)); fd.append('traveler_id',"<?php echo $traveler_id; ?>"); fd.append('page_type',pageType);
            try { var res = await fetch(window.location.href,{method:'POST',body:fd}); var result = await res.json(); if(result.success) { btn.innerHTML='<i class="fas fa-check"></i> SAVED!'; btn.classList.remove('btn-save-gradient'); btn.classList.add('bg-black'); setTimeout(()=>{ window.history.back(); },1200); } else throw new Error('Save failed'); } catch(err){ alert('Error: '+err.message); btn.disabled=false; btn.innerHTML='<i class="fas fa-save"></i> SAVE DATA'; }
        }
    </script>
</body>
</html>