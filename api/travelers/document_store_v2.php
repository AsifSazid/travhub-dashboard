<?php
/**
 * Document Store v2  —  TravHub v2 Document Intelligence Pipeline
 * =================================================================
 * The core new feature. One request ingests N files for a traveler and
 * builds an accumulating, AI-generated profile.
 *
 * FLOW (mirrors Documentation v2 §4 Flow 2):
 *   Step 1  Receive uploads (4 methods) -> save to tmp/{batch_uuid}/
 *   Step 2  Create batch shell           (batches row, documents/summary NULL)
 *   Step 3  Per-file Gemini loop         (N calls) -> insert one documents row each
 *   Step 4  Update batch                 (documents[] manifest + combined summary, +1 call)
 *   Step 5  Update traveler summary       (merge old + batch, +1 call) with history snapshot
 *   Step 6  Store files                  (PDF->PNG via Imagick; route to doc_type folder; mirror SMB)
 *   Cleanup tmp on success
 *
 * Total Gemini calls = N (per-file) + 1 (batch combine) + 1 (traveler merge) = N + 2
 *
 * UPLOAD INPUT (multipart/form-data):
 *   traveler_id   (GET or POST)  required
 *   files[]       file picker / drag-drop / clipboard paste (images, pdf)
 *   pasted_text   textarea content -> saved as a .txt file
 *
 * OUTPUT (JSON): full report of batch, documents, stored files, summaries, totals.
 *
 * GEMINI: same request/parse pattern as extract-document.php (gemini-2.0-flash-lite,
 *         response_mime_type application/json, ```json fence stripping).
 * STORAGE: same chain as doc_store.php (makeDir + makeSMBDir + OMV_SMB_Manager).
 */

session_start();
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';
require '../../server/make-smb-dir.php';
require_once '../../server/live_storage.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PDFs + multiple Gemini calls need headroom
ini_set('memory_limit', '1024M');
set_time_limit(600);

date_default_timezone_set('Asia/Dhaka');

// ---------------- CONFIG ----------------
$GEMINI_API_KEY = trim(@file_get_contents('../../gemini-apikey.txt'));
if (empty($GEMINI_API_KEY)) {
    echo json_encode(['success' => false, 'message' => 'Gemini API key not configured']);
    exit;
}
$SERVER_CUS_PATH = trim(@file_get_contents('../../server-name.txt'));

// The 9 canonical sub-folders. doc_type MUST resolve to one of these.
$VALID_DOC_TYPES = [
    'all_documents', 'passport_identity', 'nid',
    'personal_documents', 'professional_documents', 'financial_documents',
    'travel_history', 'photos_signature', 'countries_documents',
];
$FALLBACK_DOC_TYPE = 'all_documents';

$ALLOWED_IMAGE = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// ---------------- INPUT ----------------
$travelerId = $_GET['traveler_id'] ?? $_POST['traveler_id'] ?? null;
$pastedText = $_POST['pasted_text'] ?? null;

if (!$travelerId) {
    echo json_encode(['success' => false, 'message' => 'traveler_id is required']);
    exit;
}

try {
    // Resolve traveler (need name for folder path)
    $stmt = $pdo->prepare("SELECT sys_id, name FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    $traveler = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$traveler || empty($traveler['sys_id'])) {
        echo json_encode(['success' => false, 'message' => 'Traveler not found']);
        exit;
    }

    // =====================================================================
    // STEP 1 — Receive uploads into tmp/{batch_uuid}/
    // =====================================================================
    $batchUuid = uuidV4(); // tmp foldername; the batch row's own uuid is generated at Step 2
    $tmpDir = "../../tmp/{$batchUuid}/";
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0775, true);
    }

    $tmpFiles = []; // [ ['path'=>, 'orig'=>, 'ext'=>], ... ]
    $rejected = [];

    // 1a. files[] (picker / drag-drop / clipboard all arrive here)
    if (!empty($_FILES['files']['name'][0])) {
        foreach ($_FILES['files']['name'] as $k => $name) {
            if ($_FILES['files']['error'][$k] !== UPLOAD_ERR_OK) {
                $rejected[] = "Upload error: {$name}";
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $ALLOWED_IMAGE) && $ext !== 'pdf' && $ext !== 'txt') {
                $rejected[] = "Unsupported type: {$name}";
                continue;
            }
            $safe = uniqid('up_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
            $dest = $tmpDir . $safe;
            if (move_uploaded_file($_FILES['files']['tmp_name'][$k], $dest)) {
                $tmpFiles[] = ['path' => $dest, 'orig' => $name, 'ext' => $ext];
            } else {
                $rejected[] = "Failed to stage: {$name}";
            }
        }
    }

    // 1b. textarea -> .txt
    if (!empty($pastedText)) {
        $txtName = 'pasted_' . time() . '.txt';
        $dest = $tmpDir . $txtName;
        file_put_contents($dest, $pastedText);
        $tmpFiles[] = ['path' => $dest, 'orig' => $txtName, 'ext' => 'txt'];
    }

    if (empty($tmpFiles)) {
        cleanupTmp($tmpDir);
        echo json_encode([
            'success'  => false,
            'message'  => 'No usable files received',
            'rejected' => $rejected,
        ]);
        exit;
    }

    // =====================================================================
    // STEP 2 — Create batch shell
    // =====================================================================
    $batchIds = generateIDs('batches');
    $batchMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    $stmt = $pdo->prepare("
        INSERT INTO batches (uuid, sys_id, traveler_id, documents, summary, summary_info, meta_data)
        VALUES (:uuid, :sys_id, :traveler_id, NULL, NULL, NULL, :meta_data)
    ");
    $stmt->execute([
        ':uuid'        => $batchIds['uuid'],
        ':sys_id'      => $batchIds['sys_id'],
        ':traveler_id' => $travelerId,
        ':meta_data'   => $batchMeta,
    ]);
    $batchId = $batchIds['sys_id'];

    // Folder roots for this traveler (created lazily per doc_type in Step 6)
    $cleanSysId = preg_replace('/\s+/u', '', $traveler['sys_id']);
    $cleanName  = preg_replace('/\s+/u', '', $traveler['name']);
    $travelerFolder = "{$cleanSysId}_{$cleanName}";
    $cloudBase = "{$SERVER_CUS_PATH}_travelers/{$travelerFolder}";

    // =====================================================================
    // STEP 3 — Per-file Gemini loop  (N calls) -> documents rows
    // =====================================================================
    $documentsOut = [];       // for the response
    $perFileTokenTotal = 0;
    $perFileTimeTotal  = 0.0;
    $storedFiles = [];        // collected for Step 6 storage
    $loopErrors = [];

    foreach ($tmpFiles as $tf) {
        try {
            // Encode for Gemini. For PDFs we send up to 3 rasterized pages (like extract-document.php).
            $geminiParts = buildGeminiPartsForFile($tf['path'], $tf['ext']);
            $totalPages  = $geminiParts['total_pages'];

            // One Gemini call: classify + summarize + structured JSON
            $call = geminiVisionExtract($GEMINI_API_KEY, $geminiParts['parts']);
            $docData = $call['json'] ?? [];

            // Resolve doc_type to one of the 9 folders
            $docType = strtolower(trim($docData['doc_type'] ?? ''));
            if (!in_array($docType, $VALID_DOC_TYPES, true)) {
                $docType = $FALLBACK_DOC_TYPE;
            }

            $docSummary = trim((string)($docData['doc_summary'] ?? ''));
            $docJson    = $docData['doc_json'] ?? [];
            $suggested  = trim((string)($docData['suggested_filename'] ?? ''));
            // Model may report pages; trust the rasterizer's count for storage
            $reportedPages = (int)($docData['total_pages'] ?? $totalPages);
            if ($reportedPages < 1) $reportedPages = $totalPages;

            // Insert documents row
            $docIds  = generateIDs('documents');
            $docMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
            $docSummaryInfo = $call['summary_info']; // {taken_token, time} for THIS file

            $stmt = $pdo->prepare("
                INSERT INTO documents
                    (uuid, sys_id, traveler_id, batch_id, doc_type, doc_json, doc_summary, summary_info, total_pages, meta_data)
                VALUES
                    (:uuid, :sys_id, :traveler_id, :batch_id, :doc_type, :doc_json, :doc_summary, :summary_info, :total_pages, :meta_data)
            ");
            $stmt->execute([
                ':uuid'         => $docIds['uuid'],
                ':sys_id'       => $docIds['sys_id'],
                ':traveler_id'  => $travelerId,
                ':batch_id'     => $batchId,
                ':doc_type'     => $docType,
                ':doc_json'     => json_encode($docJson, JSON_UNESCAPED_UNICODE),
                ':doc_summary'  => $docSummary,
                ':summary_info' => json_encode($docSummaryInfo, JSON_UNESCAPED_UNICODE),
                ':total_pages'  => $reportedPages,
                ':meta_data'    => $docMeta,
            ]);

            $perFileTokenTotal += (int)$docSummaryInfo['taken_token'];
            $perFileTimeTotal  += (float)$docSummaryInfo['time'];

            // Queue this file for storage in its resolved doc_type folder (Step 6)
            $storedFiles[] = [
                'tmp'        => $tf['path'],
                'orig'       => $tf['orig'],
                'ext'        => $tf['ext'],
                'doc_type'   => $docType,
                'suggested'  => $suggested,
                'doc_sys_id' => $docIds['sys_id'],
            ];

            $documentsOut[] = [
                'sys_id'      => $docIds['sys_id'],
                'doc_type'    => $docType,
                'total_pages' => $reportedPages,
                'summary_info'=> $docSummaryInfo,
            ];

        } catch (Exception $e) {
            $loopErrors[] = "{$tf['orig']}: " . $e->getMessage();
            error_log("document_store_v2 per-file error ({$tf['orig']}): " . $e->getMessage());
        }
    }

    if (empty($documentsOut)) {
        cleanupTmp($tmpDir);
        echo json_encode([
            'success'  => false,
            'message'  => 'All files failed during AI extraction',
            'errors'   => $loopErrors,
            'batch_id' => $batchId,
        ]);
        exit;
    }

    // =====================================================================
    // STEP 4 — Update batch (manifest + combined summary, +1 Gemini call)
    // =====================================================================
    $manifest = array_map(fn($d) => ['sys_id' => $d['sys_id'], 'doc_type' => $d['doc_type']], $documentsOut);

    // Re-read doc_summaries we just inserted, so the combine sees exactly what's stored
    $stmt = $pdo->prepare("SELECT sys_id, doc_type, doc_summary FROM documents WHERE batch_id = ? ORDER BY id ASC");
    $stmt->execute([$batchId]);
    $batchDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $combine = combineBatchSummary($GEMINI_API_KEY, $batchDocs);
    $batchSummary = $combine['summary'];

    $batchTotals = [
        'taken_token' => $perFileTokenTotal + (int)$combine['summary_info']['taken_token'],
        'time'        => round($perFileTimeTotal + (float)$combine['summary_info']['time'], 2),
    ];

    $batchMeta = buildMetaData($batchMeta, $_SESSION['user_name'] ?? 'system');
    $stmt = $pdo->prepare("
        UPDATE batches
        SET documents = :documents, summary = :summary, summary_info = :summary_info, meta_data = :meta_data
        WHERE sys_id = :sys_id
    ");
    $stmt->execute([
        ':documents'    => json_encode($manifest, JSON_UNESCAPED_UNICODE),
        ':summary'      => $batchSummary,
        ':summary_info' => json_encode($batchTotals, JSON_UNESCAPED_UNICODE),
        ':meta_data'    => $batchMeta,
        ':sys_id'       => $batchId,
    ]);

    // =====================================================================
    // STEP 5 — Update traveler living summary (merge, +1 call) + history snapshot
    // =====================================================================
    $stmt = $pdo->prepare("SELECT summary, history_summary, meta_data FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    $tr = $stmt->fetch(PDO::FETCH_ASSOC);

    $oldSummary = trim((string)($tr['summary'] ?? ''));
    $now = date('d-m-Y H:i');

    $history = json_decode($tr['history_summary'] ?? '[]', true);
    if (!is_array($history)) $history = [];
    if ($oldSummary !== '') {
        $history[] = ['text' => $oldSummary, 'date' => $now];
    }

    if ($oldSummary === '') {
        $newTravelerSummary = $batchSummary;          // seed
        $travelerSummaryInfo = ['taken_token' => 0, 'time' => 0];
    } else {
        $merge = mergeTravelerSummary($GEMINI_API_KEY, $oldSummary, $batchSummary);
        $newTravelerSummary = $merge['summary'];
        $travelerSummaryInfo = $merge['summary_info'];
    }

    $travelerMeta = buildMetaData($tr['meta_data'] ?? null, $_SESSION['user_name'] ?? 'system');
    $stmt = $pdo->prepare("
        UPDATE travelers
        SET summary = :summary, history_summary = :history_summary,
            summary_info = :summary_info, meta_data = :meta_data
        WHERE sys_id = :sys_id
    ");
    $stmt->execute([
        ':summary'         => $newTravelerSummary,
        ':history_summary' => json_encode($history, JSON_UNESCAPED_UNICODE),
        ':summary_info'    => json_encode($travelerSummaryInfo, JSON_UNESCAPED_UNICODE),
        ':meta_data'       => $travelerMeta,
        ':sys_id'          => $travelerId,
    ]);

    // =====================================================================
    // STEP 6 — Store files in resolved doc_type folders (local + SMB)
    // =====================================================================
    $storageReport = [];
    foreach ($storedFiles as $sf) {
        $docType = $sf['doc_type'];

        // Local + SMB folder for this doc_type
        $localDir = makeDir("travelers/{$travelerFolder}", $docType);
        $cloudDir = makeSMBDir($cloudBase, $docType);
        $cloudDir = rtrim($cloudDir, '/');
        $cloudOk  = !str_starts_with($cloudDir, '❌');

        $baseName = sanitizeFilename($sf['suggested'] !== '' ? $sf['suggested'] : pathinfo($sf['orig'], PATHINFO_FILENAME));

        if ($sf['ext'] === 'pdf') {
            // PDF -> N PNGs (Imagick), original discarded
            $pngs = pdfToPngs($sf['tmp'], $baseName, $localDir);
            foreach ($pngs as $png) {
                if ($cloudOk) fileSaveinSMB($png['path'], $cloudDir, $png['name']);
                $storageReport[] = [
                    'doc_sys_id' => $sf['doc_sys_id'],
                    'stored_as'  => $png['name'],
                    'doc_type'   => $docType,
                    'page'       => $png['page'],
                ];
            }
        } elseif ($sf['ext'] === 'txt') {
            $finalName = time() . '_' . $baseName . '.txt';
            $finalPath = $localDir . '/' . $finalName;
            copy($sf['tmp'], $finalPath);
            if ($cloudOk) fileSaveinSMB($finalPath, $cloudDir, $finalName);
            $storageReport[] = ['doc_sys_id' => $sf['doc_sys_id'], 'stored_as' => $finalName, 'doc_type' => $docType, 'page' => 1];
        } else {
            // Image: store as-is
            $finalName = time() . '_' . $baseName . '.' . $sf['ext'];
            $finalPath = $localDir . '/' . $finalName;
            copy($sf['tmp'], $finalPath);
            if ($cloudOk) fileSaveinSMB($finalPath, $cloudDir, $finalName);
            $storageReport[] = ['doc_sys_id' => $sf['doc_sys_id'], 'stored_as' => $finalName, 'doc_type' => $docType, 'page' => 1];
        }
    }

    // Cleanup tmp now that storage is done
    cleanupTmp($tmpDir);

    // =====================================================================
    // RESPONSE
    // =====================================================================
    echo json_encode([
        'success'   => true,
        'message'   => count($documentsOut) . ' document(s) processed in batch ' . $batchId,
        'batch_id'  => $batchId,
        'documents' => $documentsOut,
        'stored'    => $storageReport,
        'batch_summary'        => $batchSummary,
        'batch_summary_info'   => $batchTotals,
        'traveler_summary'     => $newTravelerSummary,
        'traveler_summary_info'=> $travelerSummaryInfo,
        'gemini_calls'         => count($documentsOut) + 2, // N + 2
        'warnings'  => array_merge($rejected, $loopErrors),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($tmpDir)) cleanupTmp($tmpDir);
    error_log('document_store_v2 fatal: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}


/* =========================================================================
 * HELPERS
 * ========================================================================= */

/** Remove a tmp dir and its contents. */
function cleanupTmp(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (glob(rtrim($dir, '/') . '/*') as $f) {
        if (is_file($f)) @unlink($f);
    }
    @rmdir($dir);
}

/** Make a filesystem-safe base name (no extension). */
function sanitizeFilename(string $name): string
{
    $name = pathinfo($name, PATHINFO_FILENAME); // strip any extension the model added
    $name = preg_replace('/[^a-zA-Z0-9]+/', '_', $name);
    $name = trim($name, '_');
    return $name !== '' ? substr($name, 0, 80) : 'document';
}

/** SMB mirror helper (same shape as doc_store.php::fileSaveinSMB). */
function fileSaveinSMB(string $localFilePath, string $cloudFolderPath, string $fileName)
{
    $omv = new OMV_SMB_Manager();
    $destPath = rtrim($cloudFolderPath, '/') . '/' . $fileName;
    $status = $omv->paste_file($localFilePath, $destPath);
    if ($status !== true) {
        error_log("SMB mirror failed: {$destPath} :: " . (is_string($status) ? $status : 'unknown'));
        return false;
    }
    return true;
}

/** PDF -> per-page PNGs at 150 DPI (same approach as doc_store.php). */
function pdfToPngs(string $pdfPath, string $baseName, string $localDir): array
{
    $out = [];
    if (!extension_loaded('imagick')) {
        error_log('Imagick not loaded; cannot convert PDF: ' . $pdfPath);
        return $out;
    }
    try {
        $imagick = new Imagick();
        $imagick->setResolution(150, 150);
        $imagick->readImage($pdfPath);
        foreach ($imagick as $pageNum => $page) {
            $page->setImageFormat('png');
            $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            $pngName = time() . '_' . $baseName . '_page_' . ($pageNum + 1) . '.png';
            $pngPath = $localDir . '/' . $pngName;
            $page->writeImage($pngPath);
            if (file_exists($pngPath)) {
                $out[] = ['path' => $pngPath, 'name' => $pngName, 'page' => $pageNum + 1];
            }
        }
        $imagick->clear();
        $imagick->destroy();
    } catch (Exception $e) {
        error_log('pdfToPngs error: ' . $e->getMessage());
    }
    return $out;
}

/** Magic-byte MIME detection (same as extract-document.php). */
function detectMimeType(string $filePath): string
{
    $handle = @fopen($filePath, 'rb');
    if (!$handle) return 'image/jpeg';
    $bytes = fread($handle, 12);
    fclose($handle);
    $hex = strtoupper(bin2hex($bytes));
    if (strpos($hex, '89504E47') === 0) return 'image/png';
    if (strpos($hex, 'FFD8FF') === 0) return 'image/jpeg';
    if (strpos($hex, '52494646') === 0 && strpos($hex, '57454250') !== false) return 'image/webp';
    if (strpos($hex, '47494638') === 0) return 'image/gif';
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    return ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'][$ext] ?? 'image/jpeg';
}

/**
 * Build Gemini "parts" for one file + the v2 classification/summary prompt.
 * Returns ['parts' => [...], 'total_pages' => int].
 * - txt   : prompt + the text inline
 * - image : prompt + 1 inline_data
 * - pdf   : prompt + up to 3 rasterized PNG pages (total_pages = actual page count)
 */
function buildGeminiPartsForFile(string $path, string $ext): array
{
    $prompt = buildV2Prompt();

    if ($ext === 'txt') {
        $text = (string)file_get_contents($path);
        $parts = [['text' => $prompt . "\n\n=== DOCUMENT TEXT ===\n" . $text]];
        return ['parts' => $parts, 'total_pages' => 1];
    }

    if ($ext === 'pdf') {
        if (!extension_loaded('imagick')) {
            throw new Exception('PDF processing requires Imagick');
        }
        $imagick = new Imagick();
        $imagick->setResolution(150, 150);
        $imagick->readImage($path);
        $numPages = $imagick->getNumberImages();
        $send = min($numPages, 3);
        $parts = [['text' => $prompt]];
        for ($i = 0; $i < $send; $i++) {
            $imagick->setIteratorIndex($i);
            $imagick->setImageFormat('png');
            $b64 = base64_encode($imagick->getImageBlob());
            if ($b64 === '') continue;
            $parts[] = ['inline_data' => ['mime_type' => 'image/png', 'data' => $b64]];
        }
        $imagick->clear();
        $imagick->destroy();
        if (count($parts) <= 1) {
            throw new Exception('Failed to rasterize PDF pages');
        }
        return ['parts' => $parts, 'total_pages' => $numPages];
    }

    // image
    $mime = detectMimeType($path);
    $b64  = base64_encode((string)file_get_contents($path));
    if ($b64 === '') {
        throw new Exception('Failed to encode image');
    }
    $parts = [
        ['text' => $prompt],
        ['inline_data' => ['mime_type' => $mime, 'data' => $b64]],
    ];
    return ['parts' => $parts, 'total_pages' => 1];
}

/** The expanded v2 prompt (Documentation v2 §6.3). */
function buildV2Prompt(): string
{
    return <<<PROMPT
You are an intelligent document classifier and extractor for a traveler-profile system.
Analyze the document provided (image, PDF pages, or text) and return ONLY a JSON object
with EXACTLY this shape and nothing else:

{
  "suggested_filename": "short_descriptive_name_without_extension",
  "doc_type": "one of: passport_identity | nid | travel_history | personal_documents | professional_documents | financial_documents | photos_signature | countries_documents | all_documents",
  "doc_summary": "A clear narrative, written in neutral third person, describing what this document tells us about the traveler. State concrete facts (names, dates, numbers, nationality, addresses, issuer). 2-5 sentences.",
  "doc_json": { "...": "all structured fields you can read from the document, as key/value pairs; use nested objects where natural; null for unreadable fields" },
  "total_pages": 1
}

RULES:
- doc_type MUST be exactly one of the nine values listed. If unsure, use "all_documents".
- ALL dates in doc_json MUST be formatted DD-MM-YYYY.
- Do NOT wrap the JSON in markdown fences. Do NOT add commentary.
- Never invent data that is not present; use null for missing fields.
PROMPT;
}

/**
 * Per-file VISION extraction call (Step 3). Mirrors extract-document.php's
 * executeGeminiRequest, but parses the v2 JSON shape and captures token/time.
 * Returns ['json' => array|null, 'raw' => string, 'summary_info' => {taken_token, time}].
 */
function geminiVisionExtract(string $apiKey, array $parts): array
{
    $model = 'gemini-2.0-flash-lite';
    $payload = [
        'contents' => [['parts' => $parts]],
        'generationConfig' => [
            'response_mime_type' => 'application/json',
            'temperature'        => 0.1,
            'maxOutputTokens'    => 2048,
        ],
    ];

    $start = microtime(true);
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
    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Curl error: ' . $err);
    }
    curl_close($ch);
    $elapsed = round(microtime(true) - $start, 2);

    if ($httpCode !== 200) {
        $errData = json_decode($response, true);
        throw new Exception('Gemini API error: ' . ($errData['error']['message'] ?? "HTTP {$httpCode}"));
    }

    $result = json_decode($response, true);
    $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $takenToken = (int)($result['usageMetadata']['totalTokenCount'] ?? 0);

    $clean = trim(preg_replace('/^```json\s*|\s*```$/m', '', $rawText));
    $parsed = json_decode($clean, true);
    if (json_last_error() !== JSON_ERROR_NONE) $parsed = null;

    return [
        'json'         => $parsed,
        'raw'          => $rawText,
        'summary_info' => ['taken_token' => $takenToken, 'time' => $elapsed],
    ];
}

/** Combine all doc_summary into one batch narrative (Step 4). One text call. */
function combineBatchSummary(string $apiKey, array $docs): array
{
    $blocks = [];
    foreach ($docs as $i => $d) {
        $n = $i + 1;
        $type = $d['doc_type'] ?: 'unknown';
        $sum = trim((string)$d['doc_summary']);
        if ($sum === '') $sum = '(no narrative for this document)';
        $blocks[] = "Document {$n} [{$type}]:\n{$sum}";
    }
    $prompt = "Merge the following per-document summaries into ONE coherent narrative profile of the "
        . "traveler. Keep concrete facts, resolve overlaps, invent nothing, neutral third person.\n\n"
        . "Return ONLY: { \"summary\": \"...\" }\n\n" . implode("\n\n", $blocks);
    $call = geminiTextCall($apiKey, $prompt);
    $summary = isset($call['json']['summary']) && is_string($call['json']['summary'])
        ? trim($call['json']['summary']) : trim($call['raw'] ?? '');
    return ['summary' => $summary, 'summary_info' => $call['summary_info']];
}

/** Merge old living summary + batch summary (Step 5). One text call. */
function mergeTravelerSummary(string $apiKey, string $old, string $batch): array
{
    $prompt = "You maintain an evolving traveler profile. Integrate the NEW information into the "
        . "EXISTING profile: keep what is still true, add what is new, correct what is superseded, "
        . "avoid repetition, invent nothing, neutral third person.\n\n"
        . "Return ONLY: { \"summary\": \"...\" }\n\n"
        . "=== EXISTING PROFILE ===\n{$old}\n\n=== NEW INFORMATION ===\n{$batch}";
    $call = geminiTextCall($apiKey, $prompt);
    $summary = isset($call['json']['summary']) && is_string($call['json']['summary'])
        ? trim($call['json']['summary']) : trim($call['raw'] ?? '');
    if ($summary === '') $summary = trim($old . "\n\n" . $batch);
    return ['summary' => $summary, 'summary_info' => $call['summary_info']];
}

/** Text-only Gemini call (used by combine + merge). */
function geminiTextCall(string $apiKey, string $prompt): array
{
    $model = 'gemini-2.0-flash-lite';
    $payload = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'response_mime_type' => 'application/json',
            'temperature'        => 0.2,
            'maxOutputTokens'    => 2048,
        ],
    ];
    $start = microtime(true);
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 90,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Curl error: ' . $err);
    }
    curl_close($ch);
    $elapsed = round(microtime(true) - $start, 2);

    if ($httpCode !== 200) {
        $errData = json_decode($response, true);
        throw new Exception('Gemini API error: ' . ($errData['error']['message'] ?? "HTTP {$httpCode}"));
    }
    $result = json_decode($response, true);
    $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $takenToken = (int)($result['usageMetadata']['totalTokenCount'] ?? 0);
    $clean = trim(preg_replace('/^```json\s*|\s*```$/m', '', $rawText));
    $parsed = json_decode($clean, true);
    if (json_last_error() !== JSON_ERROR_NONE) $parsed = null;

    return [
        'json'         => $parsed,
        'raw'          => $rawText,
        'summary_info' => ['taken_token' => $takenToken, 'time' => $elapsed],
    ];
}