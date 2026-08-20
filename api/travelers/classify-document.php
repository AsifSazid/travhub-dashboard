<?php
/**
 * FILE PATH: api/travelers/classify-document.php
 *
 * Two modes:
 *
 * Mode 1: action=split (POST, multipart)
 *   PDF → PNG pages → save to tmp → return page tokens[]
 *   No Gemini call here.
 *
 * Mode 2: action=classify (POST, JSON)
 *   Single page token → Gemini classify → save result → return doc data
 *
 * Mode 3: action=classify (POST, multipart, image file)
 *   Single image → Gemini classify → save result → return doc data
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

ini_set('memory_limit', '512M');
set_time_limit(120);

$GEMINI_API_KEY = trim(@file_get_contents('../../gemini-apikey.txt'));
$GEMINI_MODEL   = trim(@file_get_contents('../../gemini-model.txt')) ?: 'gemini-2.5-flash';

const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
const MAX_BYTES    = 100 * 1024 * 1024;
const VALID_DOC_TYPES = [
    'passport', 'nid', 'visa', 'visa_stamp',
    'air_ticket', 'hotel_voucher', 'invitation_letter',
    'bank_statement', 'sponsor_letter',
    'employment_letter', 'education_certificate',
    'medical_report', 'vaccination_card', 'marriage_certificate', 'birth_certificate',
    'photo', 'signature', 'other',
];

if (!$GEMINI_API_KEY) jsonError('Gemini API key not configured');

// ── Route by action ───────────────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? 'classify';

if ($action === 'split') {
    handleSplit();
} else {
    handleClassify();
}

// ════════════════════════════════════════════════════════════════════════════
// handleSplit — PDF → PNG pages → return page_tokens[]
// ════════════════════════════════════════════════════════════════════════════
function handleSplit(): never
{
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonError('File upload failed');
    }

    $file    = $_FILES['file'];
    $origName= $file['name'];
    $fileSize= $file['size'];
    $tmpSrc  = $file['tmp_name'];
    $ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTS, true)) jsonError('Unsupported file type');
    if ($fileSize > MAX_BYTES) jsonError('File too large');

    // Image হলে split দরকার নেই — directly classify এ পাঠাও
    if ($ext !== 'pdf') {
        $pageToken = stageSingleImage($tmpSrc, $ext, $origName, $fileSize);
        echo json_encode([
            'success'    => true,
            'type'       => 'image',
            'page_tokens'=> [[$pageToken, $origName, 1]],
            'total_pages'=> 1,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // PDF → pages
    if (!extension_loaded('imagick')) jsonError('Imagick not available');

    $batchDir = rtrim(sys_get_temp_dir(), '/') . '/travhub_classify/' . bin2hex(random_bytes(8)) . '/';
    mkdir($batchDir, 0775, true);

    // Stage original PDF
    $pdfPath = $batchDir . 'original.pdf';
    move_uploaded_file($tmpSrc, $pdfPath);

    $imagick = new Imagick();
    $imagick->setResolution(150, 150);
    $imagick->readImage($pdfPath);
    $totalPages = $imagick->getNumberImages();

    $pageTokens = [];
    for ($i = 0; $i < $totalPages; $i++) {
        $imagick->setIteratorIndex($i);
        $imagick->setImageFormat('png');
        $w = $imagick->getImageWidth();
        if ($w > 1200) $imagick->resizeImage(1200, 0, Imagick::FILTER_LANCZOS, 1);

        $pagePath = $batchDir . 'page_' . ($i + 1) . '.png';
        file_put_contents($pagePath, $imagick->getImageBlob());

        // প্রতিটা page এর জন্য একটা tmp token (Gemini call নেই এখনো)
        $pageToken  = bin2hex(random_bytes(16));
        $pageTokens[] = [$pageToken, $pagePath, $i + 1];
    }
    $imagick->clear();
    $imagick->destroy();

    echo json_encode([
        'success'    => true,
        'type'       => 'pdf',
        'page_tokens'=> $pageTokens,
        'total_pages'=> $totalPages,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
// handleClassify — single image/page → Gemini → token save → return
// ════════════════════════════════════════════════════════════════════════════
function handleClassify(): never
{
    global $pdo, $GEMINI_API_KEY, $GEMINI_MODEL;

    $travelerSysId  = trim($_POST['traveler_sys_id'] ?? '');
    $passportStatus = trim($_POST['passport_status']  ?? 'auto');

    if (!$travelerSysId) jsonError('traveler_sys_id is required');

    $stmt = $pdo->prepare("SELECT sys_id, name, passport_no, passport_info FROM travelers WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$travelerSysId]);
    $traveler = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$traveler) jsonError('Traveler not found');

    // Image source — either uploaded file or staged page path
    $stagedPath = trim($_POST['page_path'] ?? '');
    $origName   = trim($_POST['original_filename'] ?? 'document');
    $fileSize   = (int)($_POST['file_size'] ?? 0);
    $pageNo     = (int)($_POST['page_no'] ?? 1);
    $mime       = 'image/png';

    if ($stagedPath && file_exists($stagedPath)) {
        // Staged PNG from split
        $b64 = base64_encode(file_get_contents($stagedPath));
    } elseif (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Direct image upload
        $file     = $_FILES['file'];
        $origName = $file['name'];
        $fileSize = $file['size'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTS, true)) jsonError('Unsupported file type');
        if ($fileSize > MAX_BYTES) jsonError('File too large');

        $mimeMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
        $mime    = $mimeMap[$ext] ?? 'image/jpeg';
        $b64     = base64_encode(file_get_contents($file['tmp_name']));
        $stagedPath = $file['tmp_name'];
    } else {
        jsonError('No image provided');
    }

    // Gemini classify
    $prompt = buildClassifyPrompt($traveler, $passportStatus);
    $result = callGemini($GEMINI_API_KEY, $GEMINI_MODEL, $b64, $mime, $prompt);

    if (!$result['success']) {
        jsonError('Gemini classification failed: ' . $result['error']);
    }

    $data    = $result['data'];
    $docType = sanitizeDocType($data['doc_type'] ?? 'other');

    // Token save
    $token     = bin2hex(random_bytes(24));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours'));

    $passportAnalysis = null;
    if ($docType === 'passport') {
        $docNumber = $data['doc_number'] ?? $data['doc_data']['passport_number'] ?? '';
        $expiry    = formatDateForDb($data['expiry_date'] ?? '');
        $passportAnalysis = analyzePassport($pdo, $travelerSysId, $traveler, $docNumber, $expiry, $passportStatus, $data['doc_data'] ?? []);
    }

    $pdo->prepare("
        INSERT INTO classify_tokens
            (token, traveler_id, tmp_path, original_filename, file_size, mime_type,
             page_count, classify_result, passport_analysis, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
    ")->execute([
        $token, $travelerSysId, $stagedPath, $origName, $fileSize, $mime,
        json_encode([
            'doc_type'               => $docType,
            'doc_number'             => $data['doc_number'] ?? '',
            'suggested_filename_stem'=> sanitizeStem($data['suggested_filename_stem'] ?? $traveler['name'] . '_' . $docType),
            'issue_date'             => formatDateForDb($data['issue_date'] ?? ''),
            'expiry_date'            => formatDateForDb($data['expiry_date'] ?? ''),
            'summary'                => $data['summary'] ?? '',
            'confidence'             => (float)($data['confidence'] ?? 0.7),
            'needs_review'           => ($data['confidence'] ?? 0.7) < 0.6,
            'language'               => $data['language'] ?? 'English',
            'pages'                  => [['page_no' => $pageNo, 'page_type' => $data['page_type'] ?? 'unknown', 'country' => $data['country'] ?? null]],
            'doc_data'               => $data['doc_data'] ?? [],
        ], JSON_UNESCAPED_UNICODE),
        $passportAnalysis ? json_encode($passportAnalysis, JSON_UNESCAPED_UNICODE) : null,
        $expiresAt,
    ]);

    cleanupExpiredTokens($pdo);

    echo json_encode([
        'success'                 => true,
        'token'                   => $token,
        'doc_type'                => $docType,
        'doc_number'              => $data['doc_number'] ?? '',
        'suggested_filename_stem' => sanitizeStem($data['suggested_filename_stem'] ?? ''),
        'issue_date'              => formatDateForDb($data['issue_date'] ?? ''),
        'expiry_date'             => formatDateForDb($data['expiry_date'] ?? ''),
        'summary'                 => $data['summary'] ?? '',
        'confidence'              => (float)($data['confidence'] ?? 0.7),
        'needs_review'            => ($data['confidence'] ?? 0.7) < 0.6,
        'language'                => $data['language'] ?? 'English',
        'original_filename'       => $origName,
        'file_size'               => $fileSize,
        'mime_type'               => $mime,
        'page_count'              => 1,
        'pages'                   => [['page_no' => $pageNo, 'page_type' => $data['page_type'] ?? 'unknown', 'country' => $data['country'] ?? null]],
        'passport_analysis'       => $passportAnalysis,
        'merge_analysis'          => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
// stageSingleImage — image file tmp তে stage করো
// ════════════════════════════════════════════════════════════════════════════
function stageSingleImage(string $tmpSrc, string $ext, string $origName, int $fileSize): array
{
    $dir  = rtrim(sys_get_temp_dir(), '/') . '/travhub_classify/' . bin2hex(random_bytes(8)) . '/';
    mkdir($dir, 0775, true);
    $dest = $dir . 'original.' . $ext;
    move_uploaded_file($tmpSrc, $dest);
    return [$dest, $origName, 1];
}

// ════════════════════════════════════════════════════════════════════════════
// callGemini
// ════════════════════════════════════════════════════════════════════════════
function callGemini(string $apiKey, string $model, string $b64, string $mime, string $prompt): array
{
    $payload = json_encode([
        'contents' => [['role' => 'user', 'parts' => [
            ['text' => $prompt],
            ['inline_data' => ['mime_type' => $mime, 'data' => $b64]],
        ]]],
        'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 4096],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err || $code !== 200) {
        $b = json_decode($raw, true);
        return ['success' => false, 'error' => $err ?: ($b['error']['message'] ?? "HTTP {$code}")];
    }

    $body = json_decode($raw, true);
    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $data = parseGeminiJson($text);

    return $data ? ['success' => true, 'data' => $data] : ['success' => false, 'error' => 'JSON parse failed: ' . substr($text, 0, 200)];
}

// ════════════════════════════════════════════════════════════════════════════
// parseGeminiJson
// ════════════════════════════════════════════════════════════════════════════
function parseGeminiJson(string $text): ?array
{
    $clean = trim(preg_replace('/^```(?:json)?[\r\n\s]*|[\r\n\s]*```$/m', '', $text));
    $clean = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $clean);
    $first = strpos($clean, '{');
    $last  = strrpos($clean, '}');
    if ($first !== false && $last !== false && $last > $first) {
        $clean = substr($clean, $first, $last - $first + 1);
    }
    $data = json_decode($clean, true);
    if (json_last_error() === JSON_ERROR_NONE) return $data;
    $clean = iconv('UTF-8', 'UTF-8//IGNORE', $clean);
    $data  = json_decode($clean, true);
    return json_last_error() === JSON_ERROR_NONE ? $data : null;
}

// ════════════════════════════════════════════════════════════════════════════
// buildClassifyPrompt
// ════════════════════════════════════════════════════════════════════════════
function buildClassifyPrompt(array $traveler, string $passportStatusHint): string
{
    $hint = $passportStatusHint === 'current' ? 'User says this is CURRENT passport.' :
            ($passportStatusHint === 'previous' ? 'User says this is PREVIOUS passport.' : '');

    $name = $traveler['name'];
    return <<<PROMPT
Analyze this document for traveler: {$name}. {$hint}

Return ONLY a JSON object:
{
  "doc_type": "<passport|nid|visa|visa_stamp|air_ticket|hotel_voucher|invitation_letter|bank_statement|sponsor_letter|employment_letter|education_certificate|medical_report|vaccination_card|marriage_certificate|birth_certificate|photo|signature|other>",
  "doc_number": "<document number or null>",
  "suggested_filename_stem": "<short_snake_case_name>",
  "issue_date": "<DD-MM-YYYY or null>",
  "expiry_date": "<DD-MM-YYYY or null>",
  "language": "<English|Bengali|Arabic|etc>",
  "confidence": <0.0 to 1.0>,
  "needs_review": <true|false>,
  "summary": "<2-4 sentence narrative>",
  "page_type": "<bio_page|visa_page|visa_stamp|entry_stamp|exit_stamp|nid_front|nid_back|other>",
  "country": "<country name or null>",
  "doc_data": { "<all structured fields>" }
}
Dates in DD-MM-YYYY. Return ONLY JSON.
PROMPT;
}

// ════════════════════════════════════════════════════════════════════════════
// analyzePassport
// ════════════════════════════════════════════════════════════════════════════
function analyzePassport(PDO $pdo, string $travelerSysId, array $traveler, string $newPassportNo, string $newExpiry, string $userHint, array $docData): array
{
    $existingNo   = $traveler['passport_no'] ?? '';
    $existingInfo = json_decode($traveler['passport_info'] ?? '[]', true) ?: [];
    $existingExpiry = '';
    if (!empty($existingInfo[0]['bio_info']['date_of_expiry'])) {
        $existingExpiry = formatDateForDb($existingInfo[0]['bio_info']['date_of_expiry']);
    }

    if (!$existingNo)                        $scenario = 'first_time';
    elseif ($existingNo === $newPassportNo)  $scenario = 'matches_existing_current';
    elseif ($userHint === 'previous')       $scenario = 'historical_upload';
    else {
        $scenario = 'renewal_demote_old';
        if ($userHint === 'auto' && $newExpiry && $existingExpiry) {
            $newTs = strtotime(str_replace(['.','/'], '-', $newExpiry));
            $oldTs = strtotime(str_replace(['.','/'], '-', $existingExpiry));
            if ($newTs && $oldTs && $newTs < $oldTs) $scenario = 'historical_upload';
        }
    }

    $resolvedStatus = in_array($scenario, ['first_time', 'matches_existing_current', 'renewal_demote_old']) ? 'current' : 'previous';

    return [
        'scenario'             => $scenario,
        'resolved_status'      => $resolvedStatus,
        'new_passport_no'      => $newPassportNo,
        'existing_passport_no' => $existingNo,
        'bio_diff'             => [],
    ];
}

// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════
function sanitizeDocType(string $t): string
{
    return in_array($t, VALID_DOC_TYPES, true) ? $t : 'other';
}

function formatDateForDb(string $raw): string
{
    $raw = trim($raw);
    if (!$raw) return '';
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) return $raw;
    return '';
}

function sanitizeStem(string $name): string
{
    return substr(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($name)), '_'), 0, 80);
}

function cleanupExpiredTokens(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SELECT tmp_path FROM classify_tokens WHERE expires_at < NOW()");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $path) {
            foreach (explode('|', $path) as $p) {
                if ($p && file_exists($p)) @unlink($p);
            }
        }
        $pdo->exec("DELETE FROM classify_tokens WHERE expires_at < NOW()");
    } catch (Throwable $e) {}
}

function jsonError(string $msg): never
{
    echo json_encode(['success' => false, 'message' => $msg, 'duplicate' => false, 'layer' => null]);
    exit;
}