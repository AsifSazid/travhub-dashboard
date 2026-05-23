<?php
/**
 * Summarize Traveler API  —  TravHub v2 Pipeline (Step 5)
 *
 * Merges a batch's combined summary into the traveler's LIVING summary.
 * This is the "accumulating knowledge" step:
 *   1. Snapshot the current travelers.summary into travelers.history_summary[]
 *      as { text, date } BEFORE overwriting.
 *   2. Gemini merge( old_summary + batch_summary ) -> new living summary.
 *   3. travelers.summary_info = { taken_token, time } for THIS merge call only.
 *   4. Append audit entry to travelers.meta_data.
 *
 * Input (JSON body):
 *   { "traveler_id": "THR-TR-26-00K001", "batch_id": "THR-BT-26-00K001" }
 *
 * Output (JSON):
 *   { success, message, traveler_id, summary, summary_info, history_count }
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
$travelerId = $data['traveler_id'] ?? null;
$batchId    = $data['batch_id'] ?? null;

if (!$travelerId || !$batchId) {
    echo json_encode(['success' => false, 'message' => 'traveler_id and batch_id are required']);
    exit;
}

try {
    // 1. Load current traveler summary + history + meta
    $stmt = $pdo->prepare("SELECT summary, history_summary, meta_data FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    $traveler = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$traveler) {
        echo json_encode(['success' => false, 'message' => 'Traveler not found']);
        exit;
    }

    // 2. Load the batch summary we are merging in
    $stmt = $pdo->prepare("SELECT summary FROM batches WHERE sys_id = ? AND traveler_id = ?");
    $stmt->execute([$batchId, $travelerId]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$batch) {
        echo json_encode(['success' => false, 'message' => 'Batch not found for this traveler']);
        exit;
    }

    $oldSummary   = trim((string)($traveler['summary'] ?? ''));
    $batchSummary = trim((string)($batch['summary'] ?? ''));

    if ($batchSummary === '') {
        echo json_encode(['success' => false, 'message' => 'Batch has no summary to merge']);
        exit;
    }

    // 3. Snapshot the CURRENT summary into history BEFORE overwriting (only if non-empty)
    date_default_timezone_set('Asia/Dhaka');
    $now = date('d-m-Y H:i');

    $history = json_decode($traveler['history_summary'] ?? '[]', true);
    if (!is_array($history)) {
        $history = [];
    }
    if ($oldSummary !== '') {
        $history[] = ['text' => $oldSummary, 'date' => $now];
    }

    // 4. Merge via one Gemini call (or seed directly if no prior summary)
    if ($oldSummary === '') {
        // First batch ever for this traveler: the batch summary becomes the living summary.
        // No merge call needed -> zero token cost recorded.
        $newSummary  = $batchSummary;
        $summaryInfo = ['taken_token' => 0, 'time' => 0];
    } else {
        $merge       = mergeTravelerSummary($GEMINI_API_KEY, $oldSummary, $batchSummary);
        $newSummary  = $merge['summary'];
        $summaryInfo = $merge['summary_info'];
    }

    // 5. Append audit entry
    $metaDataJson = buildMetaData($traveler['meta_data'] ?? null, $_SESSION['user_name'] ?? 'system');

    // 6. Persist
    $stmt = $pdo->prepare("
        UPDATE travelers
        SET summary = :summary,
            history_summary = :history_summary,
            summary_info = :summary_info,
            meta_data = :meta_data
        WHERE sys_id = :sys_id
    ");
    $stmt->execute([
        ':summary'         => $newSummary,
        ':history_summary' => json_encode($history, JSON_UNESCAPED_UNICODE),
        ':summary_info'    => json_encode($summaryInfo, JSON_UNESCAPED_UNICODE),
        ':meta_data'       => $metaDataJson,
        ':sys_id'          => $travelerId,
    ]);

    echo json_encode([
        'success'       => true,
        'message'       => 'Traveler summary updated',
        'traveler_id'   => $travelerId,
        'summary'       => $newSummary,
        'summary_info'  => $summaryInfo,
        'history_count' => count($history),
    ]);

} catch (Exception $e) {
    error_log('summarize_traveler error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Merge the previous living summary with the new batch summary into one updated narrative.
 * One Gemini call. Returns ['summary' => string, 'summary_info' => {taken_token, time}].
 */
function mergeTravelerSummary(string $apiKey, string $oldSummary, string $batchSummary): array
{
    $prompt = "You maintain a single, evolving profile narrative of a traveler. Below is the EXISTING "
        . "profile, followed by NEW information extracted from a fresh batch of documents. Produce an "
        . "UPDATED profile that integrates the new information into the existing one: keep everything "
        . "still true, add what is new, correct anything the new documents clearly supersede, and avoid "
        . "repetition. Do not invent facts. Write in neutral third person, concise.\n\n"
        . "Return ONLY a JSON object of the exact form:\n"
        . "{ \"summary\": \"...the updated profile narrative...\" }\n\n"
        . "=== EXISTING PROFILE ===\n" . $oldSummary . "\n\n"
        . "=== NEW INFORMATION (this batch) ===\n" . $batchSummary;

    $call = geminiTextCall($apiKey, $prompt);

    $summary = '';
    if (isset($call['json']['summary']) && is_string($call['json']['summary'])) {
        $summary = trim($call['json']['summary']);
    } else {
        $summary = trim($call['raw'] ?? '');
    }

    // Safety: never wipe a profile to empty on a parse miss
    if ($summary === '') {
        $summary = trim($oldSummary . "\n\n" . $batchSummary);
    }

    return [
        'summary'      => $summary,
        'summary_info' => $call['summary_info'],
    ];
}

/**
 * Text-only Gemini call mirroring extract-document.php's request/parse pattern.
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