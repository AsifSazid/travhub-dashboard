<?php
/**
 * FILE PATH: api/travelers/classify-document.php
 *
 * Phase 3 — Step 2: File receive → Gemini classify → token return
 *
 * smart-upload.php থেকে একটা file আসে (multipart/form-data)।
 * Gemini দিয়ে classify + extract করে result classify_tokens table এ
 * রাখে, frontend কে একটা token দেয়।
 * File টা tmp তে থাকে — commit না হওয়া পর্যন্ত NAS এ যায় না।
 *
 * INPUT (multipart/form-data):
 *   file              — uploaded file (image/pdf)
 *   traveler_sys_id   — travelers.sys_id
 *   passport_status   — auto | current | previous (user এর radio choice)
 *
 * OUTPUT (JSON):
 *   success, token, doc_type, doc_number, suggested_filename_stem,
 *   issue_date, expiry_date, summary, confidence, needs_review,
 *   language, page_count, pages[],
 *   original_filename, file_size, mime_type,
 *   passport_analysis (passport এর জন্য),
 *   merge_analysis (duplicate doc এর জন্য)
 */

session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');
set_time_limit(120);

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

// api_bootstrap.php already sets Content-Type: application/json

// ── Gemini direct call (ai-gemini.php এর geminiCallWithFile use করব) ──────
$GEMINI_API_KEY = trim(@file_get_contents('../../gemini-apikey.txt'));
$GEMINI_MODEL   = 'gemini-2.5-flash';

// ── Allowed types ───────────────────────────────────────────────────────────
const ALLOWED_EXTS  = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
const MAX_BYTES     = 100 * 1024 * 1024; // 100 MB

// ── Valid doc_types — must match doc_type_registry.doc_type exactly ──────────
// Valid doc_types — must match doc_type_registry.doc_type exactly
const VALID_DOC_TYPES = [
    'passport', 'nid',
    'visa', 'visa_stamp',
    'air_ticket', 'hotel_voucher', 'invitation_letter',
    'bank_statement', 'sponsor_letter',
    'employment_letter', 'education_certificate',
    'medical_report', 'vaccination_card', 'marriage_certificate', 'birth_certificate',
    'photo', 'signature',
    'other',
];

// ════════════════════════════════════════════════════════════════════════════
// INPUT VALIDATION
// ════════════════════════════════════════════════════════════════════════════

if (empty($GEMINI_API_KEY)) {
    jsonError('Gemini API key not configured');
}

$travelerSysId  = trim($_POST['traveler_sys_id'] ?? '');
$passportStatus = trim($_POST['passport_status']  ?? 'auto'); // auto|current|previous

if (!$travelerSysId) {
    jsonError('traveler_sys_id is required');
}

// Traveler exist করে কিনা confirm করো
$stmt = $pdo->prepare("SELECT sys_id, name, passport_no, passport_info FROM travelers WHERE sys_id = ? LIMIT 1");
$stmt->execute([$travelerSysId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$traveler) {
    jsonError('Traveler not found');
}

// File validation
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['file']['error'] ?? -1;
    jsonError('File upload failed (error code: ' . $errCode . ')');
}

$file     = $_FILES['file'];
$origName = $file['name'];
$fileSize = $file['size'];
$tmpSrc   = $file['tmp_name'];
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if (!in_array($ext, ALLOWED_EXTS, true)) {
    jsonError('Unsupported file type: .' . $ext);
}
if ($fileSize > MAX_BYTES) {
    jsonError('File too large (' . round($fileSize / 1024 / 1024, 1) . ' MB, max 100 MB)');
}

// ════════════════════════════════════════════════════════════════════════════
// STEP 1 — Stage file to tmp
// ════════════════════════════════════════════════════════════════════════════

$token   = bin2hex(random_bytes(24)); // 48-char hex token
$tmpDir  = rtrim(sys_get_temp_dir(), '/') . '/travhub_classify/';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0775, true);

$safeName = $token . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
$tmpPath  = $tmpDir . $safeName;

if (!move_uploaded_file($tmpSrc, $tmpPath)) {
    jsonError('Failed to stage file to tmp');
}

// ════════════════════════════════════════════════════════════════════════════
// STEP 2 — Build Gemini parts (image → base64, PDF → rasterize up to 4 pages)
// ════════════════════════════════════════════════════════════════════════════

try {
    [$geminiParts, $pageCount, $mimeType] = buildGeminiParts($tmpPath, $ext);
} catch (Throwable $e) {
    @unlink($tmpPath);
    jsonError('File processing failed: ' . $e->getMessage());
}

// ════════════════════════════════════════════════════════════════════════════
// STEP 3 — Gemini classify + extract
// ════════════════════════════════════════════════════════════════════════════

$prompt  = buildClassifyPrompt($traveler, $passportStatus);
$gemResp = callGeminiVision($GEMINI_API_KEY, $GEMINI_MODEL, $geminiParts, $prompt);

if (!$gemResp['success']) {
    @unlink($tmpPath);
    jsonError('Gemini classification failed: ' . ($gemResp['error'] ?? 'unknown'));
}

$extracted = $gemResp['data'];

// doc_type validate + fallback
$docType = strtolower(trim($extracted['doc_type'] ?? ''));
if (!in_array($docType, VALID_DOC_TYPES, true)) {
    $docType = 'all_documents';
}

$confidence   = min(1.0, max(0.0, (float)($extracted['confidence'] ?? 0.5)));
$needsReview  = $confidence < 0.6 || !empty($extracted['needs_review']);
$language     = trim($extracted['language'] ?? 'unknown');
$docNumber    = trim($extracted['doc_number'] ?? '');
$issueDate    = formatDateForDb($extracted['issue_date'] ?? '');
$expiryDate   = formatDateForDb($extracted['expiry_date'] ?? '');
$summary      = trim($extracted['summary'] ?? '');
$filenameStem = sanitizeStem($extracted['suggested_filename_stem'] ?? '');
$pages        = normalizePages($extracted['pages'] ?? [], $pageCount);
$docData      = $extracted['doc_data'] ?? [];

if ($filenameStem === '') {
    // Fallback: traveler name + doc type
    $filenameStem = sanitizeStem(strtolower($traveler['name']) . '_' . $docType);
}

// ════════════════════════════════════════════════════════════════════════════
// STEP 4 — Passport analysis (passport_identity এর জন্য special logic)
// ════════════════════════════════════════════════════════════════════════════

$passportAnalysis = null;
if ($docType === 'passport') {
    $passportAnalysis = analyzePassport(
        $pdo,
        $travelerSysId,
        $traveler,
        $docNumber,
        $expiryDate,
        $passportStatus,
        $docData
    );
}

// ════════════════════════════════════════════════════════════════════════════
// STEP 5 — Duplicate document check (same doc_number + same doc_type already?)
// ════════════════════════════════════════════════════════════════════════════

$mergeAnalysis = null;
if ($docNumber && $docType !== 'passport') {
    // passport এর merge analysis উপরে আলাদাভাবে হয়
    $mergeAnalysis = checkDocDuplicate($pdo, $travelerSysId, $docType, $docNumber);
}

// ════════════════════════════════════════════════════════════════════════════
// STEP 6 — classify_tokens table এ save করো
// ════════════════════════════════════════════════════════════════════════════

$expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours'));

$stmt = $pdo->prepare("
    INSERT INTO classify_tokens
        (token, traveler_id, tmp_path, original_filename, file_size, mime_type,
         page_count, classify_result, passport_analysis, merge_analysis, expires_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $token,
    $travelerSysId,
    $tmpPath,
    $origName,
    $fileSize,
    $mimeType,
    $pageCount,
    json_encode([
        'doc_type'               => $docType,
        'doc_number'             => $docNumber,
        'suggested_filename_stem'=> $filenameStem,
        'issue_date'             => $issueDate,
        'expiry_date'            => $expiryDate,
        'summary'                => $summary,
        'confidence'             => $confidence,
        'needs_review'           => $needsReview,
        'language'               => $language,
        'pages'                  => $pages,
        'doc_data'               => $docData,
    ], JSON_UNESCAPED_UNICODE),
    $passportAnalysis ? json_encode($passportAnalysis, JSON_UNESCAPED_UNICODE) : null,
    $mergeAnalysis    ? json_encode($mergeAnalysis,    JSON_UNESCAPED_UNICODE) : null,
    $expiresAt,
]);

// ── Cleanup: পুরনো expired tokens + তাদের tmp files delete করো ──────────────
cleanupExpiredTokens($pdo);

// ════════════════════════════════════════════════════════════════════════════
// RESPONSE — frontend এর renderCard() এ যা লাগে সব পাঠাও
// ════════════════════════════════════════════════════════════════════════════

echo json_encode([
    'success'                 => true,
    'token'                   => $token,

    // Classification
    'doc_type'                => $docType,
    'doc_number'              => $docNumber,
    'suggested_filename_stem' => $filenameStem,
    'issue_date'              => $issueDate,
    'expiry_date'             => $expiryDate,
    'summary'                 => $summary,
    'confidence'              => $confidence,
    'needs_review'            => $needsReview,
    'language'                => $language,

    // File info
    'original_filename'       => $origName,
    'file_size'               => $fileSize,
    'mime_type'               => $mimeType,
    'page_count'              => $pageCount,
    'pages'                   => $pages,

    // Special analysis
    'passport_analysis'       => $passportAnalysis,
    'merge_analysis'          => $mergeAnalysis,
], JSON_UNESCAPED_UNICODE);


// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

/**
 * File → Gemini parts array + metadata
 * Image: 1 part (base64)
 * PDF: Imagick দিয়ে rasterize, max 4 pages পাঠাও
 * Returns: [parts[], pageCount, mimeType]
 */
function buildGeminiParts(string $path, string $ext): array
{
    if ($ext === 'pdf') {
        if (!extension_loaded('imagick')) {
            throw new Exception('PDF processing requires Imagick extension');
        }
        $imagick = new Imagick();
        $imagick->setResolution(150, 150);
        $imagick->readImage($path);
        $totalPages = $imagick->getNumberImages();
        $sendPages  = min($totalPages, 4); // max 4 page Gemini তে

        $parts = [];
        for ($i = 0; $i < $sendPages; $i++) {
            $imagick->setIteratorIndex($i);
            $imagick->setImageFormat('png');
            $b64 = base64_encode($imagick->getImageBlob());
            if ($b64 !== '') {
                $parts[] = ['inline_data' => ['mime_type' => 'image/png', 'data' => $b64]];
            }
        }
        $imagick->clear();
        $imagick->destroy();

        if (empty($parts)) {
            throw new Exception('Failed to rasterize PDF');
        }

        return [$parts, $totalPages, 'application/pdf'];
    }

    // Image
    $mimeMap = [
        'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png'  => 'image/png',  'webp' => 'image/webp',
    ];
    $mime = $mimeMap[$ext] ?? 'image/jpeg';
    $b64  = base64_encode(file_get_contents($path));
    if ($b64 === '') {
        throw new Exception('Failed to encode image');
    }

    return [
        [['inline_data' => ['mime_type' => $mime, 'data' => $b64]]],
        1,
        $mime,
    ];
}

/**
 * Gemini vision call — multipart (text prompt + image parts)
 */
function callGeminiVision(string $apiKey, string $model, array $imageParts, string $textPrompt): array
{
    $parts = array_merge(
        [['text' => $textPrompt]],
        $imageParts
    );

    $payload = json_encode([
        'contents' => [['role' => 'user', 'parts' => $parts]],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'temperature'      => 0.1,
            'maxOutputTokens'  => 4096,
        ],
    ], JSON_UNESCAPED_UNICODE);

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $ch = curl_init($url);
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

    if ($err) return ['success' => false, 'error' => 'cURL: ' . $err];
    if ($code !== 200) {
        $b = json_decode($raw, true);
        return ['success' => false, 'error' => $b['error']['message'] ?? "HTTP {$code}"];
    }

    $body = json_decode($raw, true);
    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (trim($text) === '') {
        return ['success' => false, 'error' => 'Gemini returned empty response'];
    }

    // responseMimeType: application/json হলে clean JSON আসে সরাসরি
    $clean = trim(preg_replace('/^```json\s*|\s*```$/m', '', $text));
    $data  = json_decode($clean, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'JSON parse failed: ' . json_last_error_msg(), 'raw' => $text];
    }

    return ['success' => true, 'data' => $data];
}

/**
 * Gemini prompt — document classify + extract
 * Traveler context দিলে Gemini আরো accurate হয়
 */
function buildClassifyPrompt(array $traveler, string $passportStatusHint): string
{
    $travelerCtx = "Traveler name: {$traveler['name']}";
    if ($traveler['passport_no']) {
        $travelerCtx .= " | Known passport no: {$traveler['passport_no']}";
    }

    $passportHint = '';
    if ($passportStatusHint === 'current') {
        $passportHint = 'The user has indicated this is their CURRENT passport.';
    } elseif ($passportStatusHint === 'previous') {
        $passportHint = 'The user has indicated this is a PREVIOUS/OLD passport.';
    }

    return <<<PROMPT
You are an expert travel document classifier and data extractor for a Bangladeshi travel agency.

TRAVELER CONTEXT: {$travelerCtx}
{$passportHint}

Analyze the document image(s) and return ONLY a valid JSON object with this exact structure:

{
  "doc_type": "<one of the 9 valid types>",
  "doc_number": "<primary document number or null>",
  "suggested_filename_stem": "<short_descriptive_snake_case_name>",
  "issue_date": "<DD-MM-YYYY or null>",
  "expiry_date": "<DD-MM-YYYY or null>",
  "language": "<detected language, e.g. English, Bengali, Arabic>",
  "confidence": <0.0 to 1.0>,
  "needs_review": <true if unsure, false if confident>,
  "summary": "<2-4 sentence factual narrative about this document in third person>",
  "pages": [
    {
      "page_no": 1,
      "page_type": "<bio_page | visa_stamp | entry_stamp | exit_stamp | renewal_page | nid_front | nid_back | other>",
      "country": "<country name if applicable, else null>"
    }
  ],
  "doc_data": {
    "<all structured fields you can read — see rules below>"
  }
}

VALID DOC TYPES (choose exactly one):
- passport            → passport bio page, renewal pages, old passports
- nid                 → Bangladesh National ID Card (front or back)
- visa                → visa sticker with validity dates
- visa_stamp          → entry/exit stamp, arrival/departure stamp
- air_ticket          → flight ticket, boarding pass, e-ticket
- hotel_voucher       → hotel booking confirmation, voucher
- invitation_letter   → invitation letter for visa application
- bank_statement      → bank account statement, solvency certificate
- sponsor_letter      → financial sponsor letter
- employment_letter   → employment certificate, NOC, salary certificate
- education_certificate → degree, transcript, school certificate
- medical_report      → medical fitness certificate, health report
- vaccination_card    → vaccine certificate, immunization record
- marriage_certificate → marriage certificate, nikah certificate
- birth_certificate   → birth registration certificate
- photo               → passport-size photo, portrait photo
- signature           → signature scan
- other               → anything that does not fit above categories

DOC_DATA RULES by doc_type:

For passport:
{
  "full_name": "", "surname": "", "given_names": "",
  "passport_number": "", "nationality": "", "gender": "M or F",
  "date_of_birth": "DD-MM-YYYY", "place_of_birth": "",
  "issue_date": "DD-MM-YYYY", "expiry_date": "DD-MM-YYYY",
  "issuing_authority": "", "place_of_issue": "",
  "mrz_line1": "", "mrz_line2": ""
}

For nid:
{
  "full_name": "", "name_bengali": "", "nid_number": "",
  "date_of_birth": "DD-MM-YYYY", "father_name": "", "mother_name": "",
  "address": "", "blood_group": ""
}

For visa:
{
  "visa_type": "", "visa_number": "", "country": "",
  "issue_date": "DD-MM-YYYY", "expiry_date": "DD-MM-YYYY",
  "validity_from": "DD-MM-YYYY", "validity_to": "DD-MM-YYYY",
  "entries": "Single or Double or Multiple",
  "duration_of_stay": "", "issued_at": "", "applicant_name": ""
}

For visa_stamp:
{
  "stamp_type": "entry or exit", "country": "",
  "date": "DD-MM-YYYY", "port_of_entry": ""
}

For air_ticket:
{
  "passenger_name": "", "ticket_number": "", "pnr": "",
  "airline": "", "flight_number": "",
  "departure_city": "", "arrival_city": "",
  "departure_date": "DD-MM-YYYY", "departure_time": ""
}

For hotel_voucher:
{
  "guest_name": "", "hotel_name": "", "city": "",
  "check_in": "DD-MM-YYYY", "check_out": "DD-MM-YYYY",
  "booking_reference": "", "room_type": ""
}

For bank_statement:
{
  "bank_name": "", "account_holder": "", "account_number": "",
  "period_from": "DD-MM-YYYY", "period_to": "DD-MM-YYYY",
  "closing_balance": "", "currency": ""
}

For employment_letter:
{
  "employer_name": "", "employee_name": "", "designation": "",
  "issue_date": "DD-MM-YYYY", "document_title": ""
}

For other types: extract all visible structured fields as key-value pairs.

RULES:
- ALL dates must be DD-MM-YYYY format
- null for any field you cannot read clearly
- suggested_filename_stem: lowercase, underscores, no extension, max 60 chars
  Example: "ahmed_karim_passport_BD1234567" or "uk_tourist_visa_2026"
- confidence: 0.9+ if very clear, 0.6-0.9 if readable, below 0.6 if unclear
- needs_review: true if confidence < 0.6 OR if you are unsure about doc_type
- Do NOT invent data. Use null for anything unreadable.
- Return ONLY the JSON object. No explanation, no markdown fences.
PROMPT;
}

/**
 * Passport-specific analysis:
 * এই passport টা traveler এর existing passport এর সাথে কী relation?
 * Scenario:
 *   first_time             → এই traveler এর আগে কোনো passport নেই
 *   matches_existing_current → same passport number, already current
 *   renewal_demote_old     → নতুন passport, পুরানোটা previous হবে
 *   historical_upload      → পুরানো passport upload (user said "previous")
 */
function analyzePassport(
    PDO    $pdo,
    string $travelerSysId,
    array  $traveler,
    string $newPassportNo,
    string $newExpiry,
    string $userHint,      // auto|current|previous
    array  $docData
): array {

    // Existing passport info
    $existingPassportNo = $traveler['passport_no'] ?? '';
    $existingPassportInfo = json_decode($traveler['passport_info'] ?? '[]', true);
    $existingExpiry = '';

    if (!empty($existingPassportInfo[0]['bio_info']['expiry_date'])) {
        $existingExpiry = $existingPassportInfo[0]['bio_info']['expiry_date'];
    }

    // Bio diff — কোন fields আলাদা?
    $bioDiff = [];
    if (!empty($existingPassportInfo[0]['bio_info'])) {
        $existingBio = $existingPassportInfo[0]['bio_info'];
        $checkFields = ['full_name', 'surname', 'given_names', 'date_of_birth', 'nationality', 'place_of_birth'];
        foreach ($checkFields as $field) {
            $oldVal = trim((string)($existingBio[$field] ?? ''));
            $newVal = trim((string)($docData[$field] ?? ''));
            if ($oldVal !== '' && $newVal !== '' && strtolower($oldVal) !== strtolower($newVal)) {
                $bioDiff[$field] = ['old' => $oldVal, 'new' => $newVal];
            }
        }
    }

    // Scenario determine করো
    if (!$existingPassportNo) {
        // এই traveler এর কোনো passport নেই
        $scenario       = 'first_time';
        $resolvedStatus = 'current';

    } elseif ($existingPassportNo === $newPassportNo) {
        // Same passport already on file
        $scenario       = 'matches_existing_current';
        $resolvedStatus = 'current';

    } elseif ($userHint === 'previous') {
        // User explicitly বলেছে এটা পুরানো passport
        $scenario       = 'historical_upload';
        $resolvedStatus = 'previous';

    } else {
        // Different passport number → renewal (নতুনটা current, পুরানোটা previous)
        // Auto-detect: নতুন expiry পুরানোর চেয়ে পরে হলে renewal
        $scenario       = 'renewal_demote_old';
        $resolvedStatus = 'current';

        // যদি user বলে current অথবা auto এবং new expiry আগে → historical
        if ($userHint === 'auto' && $newExpiry && $existingExpiry) {
            // formatDateForDb() দিয়ে YYYY-MM-DD এ convert করো তারপর strtotime
            $newTs      = strtotime(formatDateForDb($newExpiry));
            $existingTs = strtotime(formatDateForDb($existingExpiry));
            if ($newTs && $existingTs && $newTs < $existingTs) {
                $scenario       = 'historical_upload';
                $resolvedStatus = 'previous';
            }
        }
    }

    return [
        'scenario'        => $scenario,
        'resolved_status' => $resolvedStatus,
        'new_passport_no' => $newPassportNo,
        'existing_passport_no' => $existingPassportNo,
        'bio_diff'        => $bioDiff,
    ];
}

/**
 * Duplicate document check — same doc_number + doc_type already আছে কিনা
 */
function checkDocDuplicate(PDO $pdo, string $travelerSysId, string $docType, string $docNumber): ?array
{
    if (!$docNumber) return null;

    $stmt = $pdo->prepare("
        SELECT sys_id, page_count, status
        FROM traveler_documents
        WHERE traveler_id = ? AND doc_type = ? AND doc_number = ?
          AND status != 'deleted'
        LIMIT 1
    ");
    $stmt->execute([$travelerSysId, $docType, $docNumber]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) return null;

    return [
        'existing_sys_id'  => $existing['sys_id'],
        'existing_pages'   => (int)$existing['page_count'],
        'duplicate_pages'  => 0,   // commit এ real page comparison হবে
        'new_pages_to_add' => 1,   // optimistic — commit এ verify হবে
    ];
}

/**
 * Cleanup expired tokens + তাদের tmp files
 */
function cleanupExpiredTokens(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SELECT tmp_path FROM classify_tokens WHERE expires_at < NOW()");
        $old  = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($old as $path) {
            if ($path && file_exists($path)) @unlink($path);
        }
        $pdo->exec("DELETE FROM classify_tokens WHERE expires_at < NOW()");
    } catch (Throwable $e) {
        error_log('[classify-document] cleanup error: ' . $e->getMessage());
    }
}

/**
 * DD-MM-YYYY বা YYYY-MM-DD → MySQL DATE format (YYYY-MM-DD)
 * Invalid হলে null return করো
 */
function formatDateForDb(string $raw): string
{
    $raw = trim($raw);
    if (!$raw) return '';

    // DD-MM-YYYY
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    // YYYY-MM-DD — already correct
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }
    return '';
}

/**
 * Filename stem sanitize — lowercase alphanumeric + underscore, max 80 chars
 */
function sanitizeStem(string $name): string
{
    $name = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
    $name = trim($name, '_');
    return substr($name, 0, 80);
}

/**
 * Pages array normalize — Gemini output থেকে clean array
 */
function normalizePages(array $raw, int $totalPages): array
{
    if (empty($raw)) {
        return [['page_no' => 1, 'page_type' => 'unknown', 'country' => null]];
    }
    $out = [];
    foreach ($raw as $i => $p) {
        $out[] = [
            'page_no'   => (int)($p['page_no']   ?? $i + 1),
            'page_type' => (string)($p['page_type'] ?? 'unknown'),
            'country'   => $p['country'] ? (string)$p['country'] : null,
        ];
    }
    return $out;
}

/**
 * Error response helper
 */
function jsonError(string $msg, bool $duplicate = false, ?int $layer = null): never
{
    echo json_encode([
        'success'   => false,
        'message'   => $msg,
        'duplicate' => $duplicate,
        'layer'     => $layer,
    ]);
    exit;
}