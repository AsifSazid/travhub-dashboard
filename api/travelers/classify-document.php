<?php
/**
 * TravHub Smart Upload v3 — Document Classification API
 * ======================================================
 * STAGE 1 of upload pipeline. Does NOT write to DB or move files to SMB.
 *
 * Workflow:
 *   1. Validate upload (size, type, traveler exists)
 *   2. Layer 1 dedup: SHA-256 byte-level check
 *   3. Rasterize PDF -> JPG pages in tmp/{token}/ (or normalize single image)
 *   4. Compute pHash for each page
 *   5. Build per-doc-type aware Gemini prompt + send all pages
 *   6. Layer 2 dedup: identity-based (doc_type + doc_number match)
 *   7. Layer 3 dedup: per-page pHash merge plan for growth case
 *   8. Passport-specific: current/previous detection + bio diff vs existing
 *   9. Write sidecar.json (commit endpoint reads this)
 *   10. Return preview to frontend
 *
 * Input (multipart POST):
 *   file:             pdf/jpg/png/webp (max 20MB)
 *   traveler_sys_id:  TR-XXXXXX
 *   passport_status:  'current' | 'previous' | 'auto' (only relevant for passports)
 *
 * Output: JSON preview for frontend review
 *   {
 *     success, token, doc_type, doc_number, page_count, summary,
 *     suggested_filename_stem, confidence, needs_review,
 *     passport_analysis: {...}|null,
 *     merge_analysis: {...}|null,    // when matching existing doc
 *     pages: [{page_no, page_type, country, summary_short}]
 *   }
 */

session_start();
require_once '../../server/db_connection.php';
require_once '../../server/phash-helper.php';
require_once '../../server/doc-extraction-schemas.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ============================================================================
// Config
// ============================================================================
$GEMINI_API_KEY = trim(@file_get_contents('../../gemini-apikey.txt'));
if (empty($GEMINI_API_KEY)) {
    echo json_encode(['success' => false, 'message' => 'Gemini API key not configured']);
    exit;
}

$TMP_BASE = '../../tmp/classify/';
if (!is_dir($TMP_BASE)) {
    mkdir($TMP_BASE, 0777, true);
}

$ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
$MAX_BYTES   = 20 * 1024 * 1024;
$JPG_QUALITY = 85;
$JPG_DPI     = 150;
$MAX_PAGES   = 30;
$PHASH_THRESHOLD = 8;

// ============================================================================
// Validate request
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$travelerSysId  = trim($_POST['traveler_sys_id'] ?? '');
$passportStatus = trim($_POST['passport_status'] ?? 'auto');

if (empty($travelerSysId)) {
    echo json_encode(['success' => false, 'message' => 'traveler_sys_id required']);
    exit;
}

// Fetch traveler
$stmt = $pdo->prepare("SELECT id, sys_id, name, smb_path, server_path, passport_info
                       FROM travelers WHERE sys_id = ? LIMIT 1");
$stmt->execute([$travelerSysId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$traveler) {
    echo json_encode(['success' => false, 'message' => 'Traveler not found']);
    exit;
}

$file = $_FILES['file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $ALLOWED_EXT, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $ALLOWED_EXT)]);
    exit;
}
if ($file['size'] > $MAX_BYTES) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 20 MB)']);
    exit;
}

// ============================================================================
// Stash original + workspace
// ============================================================================
$token   = bin2hex(random_bytes(16));
$workDir = $TMP_BASE . $token . '/';
mkdir($workDir, 0777, true);

$srcPath = $workDir . 'source.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $srcPath)) {
    rmdirRecursive($workDir);
    echo json_encode(['success' => false, 'message' => 'Failed to stash upload']);
    exit;
}

// ============================================================================
// LAYER 1: Byte-level dedup
// ============================================================================
$fileHash = hash_file('sha256', $srcPath);
$dup = $pdo->prepare("SELECT sys_id, stored_basename, doc_type
                      FROM traveler_documents
                      WHERE traveler_id = ? AND file_hash = ? AND status != 'deleted' LIMIT 1");
$dup->execute([$traveler['id'], $fileHash]);
$existingByHash = $dup->fetch(PDO::FETCH_ASSOC);
if ($existingByHash) {
    rmdirRecursive($workDir);
    echo json_encode([
        'success'   => false,
        'duplicate' => true,
        'layer'     => 1,
        'message'   => "Already uploaded: {$existingByHash['stored_basename']} ({$existingByHash['doc_type']})",
        'existing'  => $existingByHash,
    ]);
    exit;
}

// ============================================================================
// Rasterize all pages -> JPG
// ============================================================================
try {
    if ($ext === 'pdf') {
        $pageFiles = rasterizePdfToJpgs($srcPath, $workDir, $JPG_DPI, $JPG_QUALITY, $MAX_PAGES);
    } else {
        $pageFiles = [normalizeImageToJpg($srcPath, $workDir . 'page_001.jpg', $JPG_QUALITY)];
    }
    if (empty($pageFiles)) throw new Exception('No pages produced');

    // ========================================================================
    // Compute pHash for every page
    // ========================================================================
    $pageHashes = [];
    foreach ($pageFiles as $pf) {
        $h = computePerceptualHash($pf);
        $pageHashes[] = $h ?: '0000000000000000';
    }

    // ========================================================================
    // Classify via Gemini (single multi-page call)
    // ========================================================================
    $classification = classifyWithGemini($pageFiles, $GEMINI_API_KEY);

    // Sanitize doc_type against the registry
    $allowedTypes = array_keys(json_decode(json_encode(
        $pdo->query("SELECT doc_type FROM doc_type_registry WHERE is_active=1")
            ->fetchAll(PDO::FETCH_COLUMN)
    ), true) ?: []);
    if (!in_array($classification['doc_type'], $pdo->query(
        "SELECT doc_type FROM doc_type_registry WHERE is_active=1")
        ->fetchAll(PDO::FETCH_COLUMN), true)) {
        $classification['doc_type'] = 'other';
    }

    // ========================================================================
    // LAYER 2: Identity-based dedup (doc_type + doc_number match)
    // LAYER 3: Per-page pHash merge plan
    // ========================================================================
    $mergeAnalysis = null;
    if (!empty($classification['doc_number'])) {
        $existingDoc = findExistingDocument(
            $pdo,
            $traveler['id'],
            $classification['doc_type'],
            $classification['doc_number']
        );
        if ($existingDoc) {
            $mergeAnalysis = buildMergeAnalysis(
                $existingDoc,
                $pageHashes,
                $PHASH_THRESHOLD
            );
        }
    }

    // If merge analysis says "zero new pages", reject as content duplicate
    if ($mergeAnalysis && count($mergeAnalysis['new_indices']) === 0) {
        rmdirRecursive($workDir);
        echo json_encode([
            'success'   => false,
            'duplicate' => true,
            'layer'     => 3,
            'message'   => "All pages already exist in document {$mergeAnalysis['existing_sys_id']}",
            'merge_analysis' => $mergeAnalysis,
        ]);
        exit;
    }

    // ========================================================================
    // Passport-specific analysis
    // ========================================================================
    $passportAnalysis = null;
    if ($classification['doc_type'] === 'passport') {
        $passportAnalysis = analyzePassport(
            $classification,
            $traveler['passport_info'],
            $passportStatus
        );
    }

    // ========================================================================
    // Build suggested filename stem
    // ========================================================================
    $stem = buildFilenameStem(
        $classification['doc_type'],
        $classification['doc_number'] ?? '',
        $traveler['name'],
        $traveler['sys_id']
    );

    // ========================================================================
    // Write sidecar (commit endpoint reads this)
    // ========================================================================
    $sidecar = [
        'token'             => $token,
        'work_dir'          => $workDir,
        'original_filename' => $file['name'],
        'original_ext'      => $ext,
        'file_size'         => $file['size'],
        'file_hash'         => $fileHash,
        'mime_type'         => mime_content_type($srcPath) ?: null,
        'traveler_id'       => (int)$traveler['id'],
        'traveler_sys_id'   => $traveler['sys_id'],
        'page_files'        => array_map('basename', $pageFiles),
        'page_hashes'       => $pageHashes,
        'page_count'        => count($pageFiles),
        'classification'    => $classification,
        'passport_analysis' => $passportAnalysis,
        'merge_analysis'    => $mergeAnalysis,
        'suggested_stem'    => $stem,
        'passport_status'   => $passportStatus,
        'created_at'        => date('c'),
    ];
    file_put_contents(
        $workDir . 'sidecar.json',
        json_encode($sidecar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    // ========================================================================
    // Response (preview for frontend)
    // ========================================================================
    echo json_encode([
        'success'                => true,
        'token'                  => $token,
        'original_filename'      => $file['name'],
        'page_count'             => count($pageFiles),
        'doc_type'               => $classification['doc_type'],
        'doc_subtype'            => $classification['doc_subtype'] ?? null,
        'doc_number'             => $classification['doc_number'] ?? '',
        'suggested_filename_stem'=> $stem,
        'summary'                => $classification['summary'],
        'language'               => $classification['language'] ?? 'en',
        'confidence'             => $classification['confidence'],
        'needs_review'           => $classification['confidence'] < 0.70,
        'issue_date'             => $classification['issue_date'] ?? null,
        'expiry_date'            => $classification['expiry_date'] ?? null,
        'doc_data'               => $classification['doc_data'] ?? null,
        'key_fields'             => $classification['key_fields'] ?? [],
        'pages'                  => $classification['pages'] ?? [],
        'passport_analysis'      => $passportAnalysis,
        'merge_analysis'         => $mergeAnalysis,
    ]);

} catch (Exception $e) {
    rmdirRecursive($workDir);
    error_log('[classify-document] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Classification failed: ' . $e->getMessage()]);
}

// ============================================================================
// Rasterization
// ============================================================================

function rasterizePdfToJpgs($pdfPath, $outDir, $dpi, $quality, $maxPages) {
    if (!extension_loaded('imagick')) {
        throw new Exception('Imagick PHP extension required');
    }
    $im = new Imagick();
    $im->setResolution($dpi, $dpi);
    $im->readImage($pdfPath);

    $numPages = $im->getNumberImages();
    if ($numPages === 0) throw new Exception('PDF has no pages');

    $pages = min($numPages, $maxPages);
    $files = [];

    for ($i = 0; $i < $pages; $i++) {
        $im->setIteratorIndex($i);
        $im->setImageFormat('jpeg');
        $im->setImageCompression(Imagick::COMPRESSION_JPEG);
        $im->setImageCompressionQuality($quality);
        $im->setImageBackgroundColor('white');
        $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        $outFile = $outDir . sprintf('page_%03d.jpg', $i + 1);
        $im->writeImage($outFile);
        $files[] = $outFile;
    }

    $im->clear();
    $im->destroy();
    return $files;
}

function normalizeImageToJpg($srcPath, $destPath, $quality) {
    $im = new Imagick($srcPath);
    $im->setImageFormat('jpeg');
    $im->setImageCompressionQuality($quality);
    $im->setImageBackgroundColor('white');
    $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
    $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
    $im->writeImage($destPath);
    $im->clear();
    $im->destroy();
    return $destPath;
}

// ============================================================================
// Gemini classification
// ============================================================================

function classifyWithGemini($pageFiles, $apiKey) {
    $prompt = buildClassificationPrompt(count($pageFiles));

    $parts = [['text' => $prompt]];
    foreach ($pageFiles as $pf) {
        $parts[] = [
            'inline_data' => [
                'mime_type' => 'image/jpeg',
                'data'      => base64_encode(file_get_contents($pf)),
            ],
        ];
    }

    $model = 'gemini-2.0-flash';
    $payload = [
        'contents' => [['parts' => $parts]],
        'generationConfig' => [
            'response_mime_type' => 'application/json',
            'temperature'        => 0.1,
            'maxOutputTokens'    => 8192,
        ],
    ];

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 120,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) { $err = curl_error($ch); curl_close($ch); throw new Exception('Curl: ' . $err); }
    curl_close($ch);

    if ($httpCode !== 200) {
        $data = json_decode($response, true);
        throw new Exception('Gemini: ' . ($data['error']['message'] ?? "HTTP {$httpCode}"));
    }

    $result = json_decode($response, true);
    $text   = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if ($text === '') throw new Exception('Empty Gemini response');

    $clean = trim(preg_replace('/^```json\s*|\s*```$/m', '', $text));
    $data  = json_decode($clean, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON parse: ' . json_last_error_msg());
    }

    // Normalize
    $data['confidence']  = isset($data['confidence']) ? max(0, min(1, (float)$data['confidence'])) : 0.5;
    $data['doc_type']    = $data['doc_type']   ?? 'other';
    $data['doc_number']  = trim((string)($data['doc_number'] ?? ''));
    $data['summary']     = $data['summary']    ?? '';
    $data['ocr_text']    = mb_substr($data['ocr_text'] ?? '', 0, 5000);
    $data['issue_date']  = normalizeIsoDate($data['issue_date']  ?? null);
    $data['expiry_date'] = normalizeIsoDate($data['expiry_date'] ?? null);
    $data['pages']       = $data['pages'] ?? [];

    return $data;
}

function buildClassificationPrompt($pageCount) {
    // Build per-doc-type schema descriptions for the prompt
    $schemaInstructions = '';
    foreach (['passport', 'nid', 'visa', 'air_ticket', 'hotel_voucher', 'bank_statement'] as $dt) {
        $schemaInstructions .= "\nIf doc_type=\"{$dt}\", include in doc_data:\n";
        $schemaInstructions .= formatSchemaForPrompt($dt) . "\n";
    }

    return <<<PROMPT
You are a travel-agency document classifier for a Bangladeshi travel platform.
You are analyzing {$pageCount} page(s) of a SINGLE uploaded document (pages in order).

Return ONE JSON object describing the WHOLE document:

{
    "doc_type": "passport | nid | visa | visa_stamp | air_ticket | hotel_voucher | invitation_letter | bank_statement | sponsor_letter | employment_letter | education_certificate | medical_report | vaccination_card | marriage_certificate | birth_certificate | photo | signature | other",
    "doc_subtype": "optional refinement like bio_page, nid_front, tourist_visa, or null",
    "doc_number": "Primary identifying number (passport no, NID no, PNR, visa no, certificate no). Empty string if none.",
    "summary": "2-3 sentence English summary. If Bengali document, summarize in English.",
    "language": "en | bn | mixed",
    "issue_date":  "YYYY-MM-DD or null (top-level)",
    "expiry_date": "YYYY-MM-DD or null (top-level)",
    "ocr_text": "Plain text transcription of all pages combined, max 5000 chars. Preserve Bengali if present.",
    "confidence": 0.0-1.0,
    "key_fields": { "free-form important fields for generic doc types" },
    "doc_data": { "structured per-doc-type extraction (see schemas below)" },
    "pages": [
        {
            "page_no": 1,
            "page_type": "bio_page | visa_stamp | entry_exit | photo_page | nid_front | nid_back | content | other",
            "country": "ISO country name if page contains country-specific content, else null",
            "summary_short": "1-line description of this specific page"
        }
    ]
}

STRUCTURED EXTRACTION SCHEMAS:
{$schemaInstructions}

For passport doc_type, doc_data.bio_info dates use DD-MM-YYYY (matches travelers.passport_info legacy format).
For all other doc_types, dates inside doc_data use YYYY-MM-DD.
Top-level issue_date/expiry_date always use YYYY-MM-DD.

If you cannot determine doc_type with confidence > 0.5, use "other".
If a doc_type has a structured schema above, populate doc_data; leave key_fields empty.
If no structured schema applies, leave doc_data null and populate key_fields with whatever you can extract.

Return ONLY the JSON object — no markdown fences, no commentary.
PROMPT;
}

// ============================================================================
// Identity-based dedup (Layer 2) + merge planning (Layer 3)
// ============================================================================

function findExistingDocument($pdo, $travelerId, $docType, $docNumber) {
    $stmt = $pdo->prepare("
        SELECT id, sys_id, doc_type, doc_number, pages, page_count, stored_basename
        FROM traveler_documents
        WHERE traveler_id = ?
          AND doc_type = ?
          AND doc_number = ?
          AND status = 'active'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$travelerId, $docType, $docNumber]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buildMergeAnalysis($existingDoc, $newPageHashes, $threshold) {
    $existingPages = !empty($existingDoc['pages'])
        ? json_decode($existingDoc['pages'], true)
        : [];

    $plan = planPageMerge($existingPages, $newPageHashes, $threshold);

    return [
        'mode'                 => 'merge_into_existing',
        'existing_doc_id'      => (int)$existingDoc['id'],
        'existing_sys_id'      => $existingDoc['sys_id'],
        'existing_doc_number'  => $existingDoc['doc_number'],
        'existing_page_count'  => (int)$existingDoc['page_count'],
        'new_indices'          => $plan['new_indices'],
        'duplicate_indices'    => $plan['duplicate_indices'],
        'matches'              => $plan['matches'],
        'new_pages_to_add'     => count($plan['new_indices']),
        'duplicate_pages'      => count($plan['duplicate_indices']),
    ];
}

// ============================================================================
// Passport-specific analysis
// ============================================================================

function analyzePassport($classification, $existingPassportInfoJson, $userChoice) {
    $newBio    = $classification['doc_data']['bio_info'] ?? null;
    $newNumber = $newBio['passport_number'] ?? $classification['doc_number'] ?? '';
    $newExpiry = $classification['expiry_date'];

    $existing = !empty($existingPassportInfoJson)
        ? json_decode($existingPassportInfoJson, true)
        : null;
    $hasExisting = is_array($existing) && !empty($existing);

    // Auto-detect status
    $autoStatus = 'current';
    if ($newExpiry) {
        $ts = strtotime($newExpiry);
        if ($ts && $ts < time()) $autoStatus = 'previous';
    }
    $resolvedStatus = ($userChoice === 'auto' || empty($userChoice)) ? $autoStatus : $userChoice;

    // Find existing current passport
    $existingCurrent = null;
    if ($hasExisting) {
        foreach ($existing as $entry) {
            $entryStatus = $entry['_metadata']['passport_status'] ?? 'current';
            if ($entryStatus === 'current') { $existingCurrent = $entry; break; }
        }
    }

    $scenario = 'first_time';
    $bioDiff  = [];

    if ($existingCurrent) {
        $existingNumber = $existingCurrent['bio_info']['passport_number'] ?? '';
        if (!empty($newNumber) && !empty($existingNumber) && strcasecmp($newNumber, $existingNumber) === 0) {
            $scenario = 'matches_existing_current';
            if ($newBio) $bioDiff = diffBio($existingCurrent['bio_info'] ?? [], $newBio);
        } else if ($resolvedStatus === 'current') {
            $scenario = 'renewal_demote_old';
        } else {
            $scenario = 'historical_upload';
        }
    }

    return [
        'resolved_status'       => $resolvedStatus,
        'user_choice'           => $userChoice,
        'auto_detected'         => $autoStatus,
        'scenario'              => $scenario,
        'has_existing'          => $hasExisting,
        'new_passport_number'   => $newNumber,
        'bio_diff'              => $bioDiff,
        'new_bio'               => $newBio,
        'existing_current_summary' => $existingCurrent ? [
            'passport_number' => $existingCurrent['bio_info']['passport_number'] ?? '',
            'full_name'       => $existingCurrent['bio_info']['full_name'] ?? '',
            'date_of_expiry'  => $existingCurrent['bio_info']['date_of_expiry'] ?? '',
        ] : null,
    ];
}

function diffBio($oldBio, $newBio) {
    $watched = [
        'passport_number','full_name','given_names','surname','nationality',
        'date_of_birth','sex','place_of_birth','date_of_issue','date_of_expiry',
        'issuing_authority','father_name','mother_name','spouse_name','permanent_address',
    ];
    $diff = [];
    foreach ($watched as $f) {
        $old = trim((string)($oldBio[$f] ?? ''));
        $new = trim((string)($newBio[$f] ?? ''));
        if ($new !== '' && $new !== $old) {
            $diff[$f] = ['old' => $old, 'new' => $new];
        }
    }
    return $diff;
}

// ============================================================================
// Helpers
// ============================================================================

function normalizeIsoDate($val) {
    if (empty($val) || $val === 'null') return null;
    $ts = strtotime($val);
    return $ts ? date('Y-m-d', $ts) : null;
}

function buildFilenameStem($docType, $docNumber, $travelerName, $sysId) {
    $docName     = sanitizeSegment($docType, true);
    $docNumberSeg= sanitizeSegment($docNumber, false);
    $travelerSeg = sanitizeSegment($travelerName, false);
    $sysIdLast6  = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $sysId), -6));

    $parts = array_filter([$docName, $docNumberSeg, $travelerSeg, $sysIdLast6], fn($s) => $s !== '');
    return implode('_', $parts);
}

function sanitizeSegment($s, $lowercase = false) {
    $s = trim((string)$s);
    if ($s === '') return '';
    $s = preg_replace('/\s+/', '-', $s);
    $s = preg_replace('/[^A-Za-z0-9\-]/', '', $s);
    $s = preg_replace('/-+/', '-', $s);
    $s = trim($s, '-');
    return $lowercase ? strtolower($s) : $s;
}

function rmdirRecursive($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        is_dir($p) ? rmdirRecursive($p) : @unlink($p);
    }
    @rmdir($dir);
}