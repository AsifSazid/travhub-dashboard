<?php
/**
 * Batch Update API  —  TravHub v2 Document Intelligence Pipeline (Step 4)
 *
 * After the per-file loop has inserted all `documents` rows, this endpoint:
 *   1. Writes the documents[] manifest onto the batch  -> [{sys_id, doc_type}, ...]
 *   2. Calls Gemini ONCE to combine every doc_summary into one batch narrative
 *   3. Records summary_info = { taken_token: sum_of_all_calls, time: sum }
 *   4. Appends an audit entry to the batch meta_data
 *
 * Input (JSON body):
 *   {
 *     "batch_id": "THR-BT-26-00K001",
 *     "prior_summary_info": { "taken_token": 4820, "time": 9.1 }   // sum of all per-file calls so far (optional)
 *   }
 *
 * Output (JSON):
 *   { success, message, batch_id, documents, summary, summary_info }
 *
 * NOTE: This file talks to Gemini using the SAME pattern as extract-document.php
 *       (gemini-2.0-flash-lite, response_mime_type application/json, fence-stripping).
 */

session_start();
require '../../server/db_connection.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(180);

$GEMINI_API_KEY = trim(@file_get_contents('../../gemini-apikey.txt'));
if (empty($GEMINI_API_KEY)) {
    echo json_encode(['success' => false, 'message' => 'Gemini API key not configured']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$batchId          = $data['batch_id'] ?? null;
$priorSummaryInfo = $data['prior_summary_info'] ?? ['taken_token' => 0, 'time' => 0];

if (!$batchId) {
    echo json_encode(['success' => false, 'message' => 'batch_id is required']);
    exit;
}

try {
    // 1. Confirm batch exists
    $stmt = $pdo->prepare("SELECT sys_id, traveler_id, meta_data FROM batches WHERE sys_id = ?");
    $stmt->execute([$batchId]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$batch) {
        echo json_encode(['success' => false, 'message' => 'Batch not found']);
        exit;
    }

    // 2. Gather all documents that belong to this batch
    $stmt = $pdo->prepare("
        SELECT sys_id, doc_type, doc_summary
        FROM documents
        WHERE batch_id = ?
        ORDER BY id ASC
    ");
    $stmt->execute([$batchId]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($docs)) {
        echo json_encode(['success' => false, 'message' => 'No documents found for this batch']);
        exit;
    }

    // 3. Build the documents[] manifest -> [{sys_id, doc_type}, ...]
    $manifest = array_map(function ($d) {
        return ['sys_id' => $d['sys_id'], 'doc_type' => $d['doc_type']];
    }, $docs);

    // 4. Combine all doc_summary narratives into one via a single Gemini call
    $combineResult   = combineBatchSummary($GEMINI_API_KEY, $docs);
    $batchSummary    = $combineResult['summary'];
    $combineCallInfo = $combineResult['summary_info']; // {taken_token, time} for THIS combine call

    // 5. summary_info = prior per-file totals + this combine call
    $totalSummaryInfo = [
        'taken_token' => (int)($priorSummaryInfo['taken_token'] ?? 0) + (int)($combineCallInfo['taken_token'] ?? 0),
        'time'        => round((float)($priorSummaryInfo['time'] ?? 0) + (float)($combineCallInfo['time'] ?? 0), 2),
    ];

    // 6. Append audit entry
    $metaDataJson = buildMetaData($batch['meta_data'] ?? null, $_SESSION['user_name'] ?? 'system');

    // 7. Persist
    $stmt = $pdo->prepare("
        UPDATE batches
        SET documents = :documents,
            summary = :summary,
            summary_info = :summary_info,
            meta_data = :meta_data
        WHERE sys_id = :sys_id
    ");
    $stmt->execute([
        ':documents'    => json_encode($manifest, JSON_UNESCAPED_UNICODE),
        ':summary'      => $batchSummary,
        ':summary_info' => json_encode($totalSummaryInfo, JSON_UNESCAPED_UNICODE),
        ':meta_data'    => $metaDataJson,
        ':sys_id'       => $batchId,
    ]);

    echo json_encode([
        'success'      => true,
        'message'      => 'Batch updated',
        'batch_id'     => $batchId,
        'documents'    => $manifest,
        'summary'      => $batchSummary,
        'summary_info' => $totalSummaryInfo,
    ]);

} catch (Exception $e) {
    error_log('batch_update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Combine all per-document narratives into one batch-level narrative.
 * One Gemini call. Returns ['summary' => string, 'summary_info' => {taken_token, time}].
 */
function combineBatchSummary(string $apiKey, array $docs): array
{
    $blocks = [];
    foreach ($docs as $i => $d) {
        $n = $i + 1;
        $type = $d['doc_type'] ?: 'unknown';
        $sum  = trim((string)$d['doc_summary']);
        if ($sum === '') {
            $sum = '(no narrative was produced for this document)';
        }
        $blocks[] = "Document {$n} [{$type}]:\n{$sum}";
    }
    $joined = implode("\n\n", $blocks);

    $prompt = "You are building a single, coherent narrative profile of a traveler from several "
        . "document summaries that were extracted separately. Merge the information below into ONE "
        . "concise, well-written paragraph (or a few short paragraphs) describing this individual. "
        . "Resolve overlaps, keep concrete facts (names, dates, numbers, nationality, addresses), and "
        . "do not invent anything not present in the inputs. Write in neutral third person.\n\n"
        . "Return ONLY a JSON object of the exact form:\n"
        . "{ \"summary\": \"...the combined narrative...\" }\n\n"
        . "Document summaries to merge:\n\n" . $joined;

    $call = geminiTextCall($apiKey, $prompt);

    $summary = '';
    if (isset($call['json']['summary']) && is_string($call['json']['summary'])) {
        $summary = trim($call['json']['summary']);
    } else {
        // Fallback: if the model didn't honor the JSON shape, keep the raw text
        $summary = trim($call['raw'] ?? '');
    }

    return [
        'summary'      => $summary,
        'summary_info' => $call['summary_info'],
    ];
}

/**
 * Text-only Gemini call mirroring extract-document.php's request/parse pattern.
 * Returns ['json' => array|null, 'raw' => string, 'summary_info' => {taken_token, time}].
 */
function geminiTextCall(string $apiKey, string $prompt): array
{
    $model = 'gemini-2.0-flash-lite';

    $payload = [
        'contents' => [[
            'parts' => [['text' => $prompt]],
        ]],
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
        $msg = $errData['error']['message'] ?? "HTTP {$httpCode}";
        throw new Exception('Gemini API error: ' . $msg);
    }

    $result = json_decode($response, true);

    $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $takenToken = (int)($result['usageMetadata']['totalTokenCount'] ?? 0);

    $clean = preg_replace('/^```json\s*|\s*```$/m', '', $rawText);
    $clean = trim($clean);
    $parsed = json_decode($clean, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $parsed = null;
    }

    return [
        'json'         => $parsed,
        'raw'          => $rawText,
        'summary_info' => ['taken_token' => $takenToken, 'time' => $elapsed],
    ];
}