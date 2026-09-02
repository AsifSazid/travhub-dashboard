<?php
/**
 * FILE PATH: api/travelers/regenerate-summary.php
 *
 * Phase 3 — Step 4: Committed documents থেকে traveler living summary rebuild করো
 *
 * smart-upload.php commit এর পরে এটা call করে।
 * traveler_documents table থেকে সব active document এর summary নিয়ে
 * Gemini দিয়ে একটা merged living summary বানায়।
 *
 * INPUT (JSON body):
 *   traveler_sys_id        — travelers.sys_id
 *   trigger                — 'document_upload' | 'manual'
 *   information_updated_for — human-readable reason (e.g. "3 documents uploaded")
 *
 * OUTPUT (JSON):
 *   success, traveler_id, summary, history_count, summary_info
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

set_time_limit(120);

$GEMINI_API_KEY = trim(@file_get_contents('../../gemini-apikey.txt'));
if (!$GEMINI_API_KEY) {
    jsonOut(['success' => false, 'message' => 'Gemini API key not configured']);
}

// ── Input ────────────────────────────────────────────────────────────────────
$body          = json_decode(file_get_contents('php://input'), true) ?: [];
$travelerSysId = trim($body['traveler_sys_id'] ?? '');
$trigger       = trim($body['trigger'] ?? 'manual');
$reason        = trim($body['information_updated_for'] ?? 'Manual regeneration');

if (!$travelerSysId) {
    jsonOut(['success' => false, 'message' => 'traveler_sys_id is required']);
}

// ── Traveler fetch ────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT sys_id, name, summary, history_summary, meta_data
    FROM travelers WHERE sys_id = ? LIMIT 1
");
$stmt->execute([$travelerSysId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$traveler) {
    jsonOut(['success' => false, 'message' => 'Traveler not found']);
}

// financial_documents ক্যাটাগরির doc_type গুলো traveler-level summary-তে
// অন্তর্ভুক্ত হবে না — sensitive financial তথ্য (account balance, sponsor
// এর আর্থিক অবস্থা ইত্যাদি) profile overview-তে চলে যাওয়া উচিত না।
// doc_type_registry অনুযায়ী financial_documents folder-এ যেসব doc_type
// map করা: bank_statement, sponsor_letter
const EXCLUDED_FROM_SUMMARY = ['bank_statement', 'sponsor_letter'];

// ── সব active documents এর summary collect করো (financial বাদে) ─────────────
$stmt = $pdo->prepare("
    SELECT doc_type, doc_number, issue_date, expiry_date, summary, doc_data
    FROM traveler_documents
    WHERE traveler_id = ? AND status = 'active' AND summary IS NOT NULL AND summary != ''
      AND doc_type NOT IN ('" . implode("','", EXCLUDED_FROM_SUMMARY) . "')
    ORDER BY doc_type, created_at DESC
");
$stmt->execute([$travelerSysId]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($documents)) {
    jsonOut(['success' => false, 'message' => 'No summary-eligible documents found (financial documents like bank statements are intentionally excluded from the traveler summary)']);
}

// ── Document summaries একটা context string এ জোড়া দাও ───────────────────────
$docContext = buildDocumentContext($documents);

// ── Old summary history তে archive করো ───────────────────────────────────────
date_default_timezone_set('Asia/Dhaka');
$now        = date('d-m-Y H:i');
// summary JSON বা plain text হতে পারে — recursive extractor দিয়ে বের করো
// (আগের bug এর কারণে DB-তে multi-level nested JSON থেকে যেতে পারে —
// একবারের unwrap দিয়ে সেটা পুরোপুরি পরিষ্কার হয় না, আর dirty data যদি
// prompt-এ EXISTING PROFILE হিসেবে পাঠানো হয়, Gemini সেই broken pattern
// দেখেই নিজের নতুন output-এও নকল করে ফেলে — এটাই এই বাগ বারবার ফিরে
// আসার আসল কারণ)
$rawSummary = trim($traveler['summary'] ?? '');
$decoded    = json_decode($rawSummary, true);
$oldSummary = extractPlainSummary($decoded, $rawSummary);

$history = json_decode($traveler['history_summary'] ?? '[]', true) ?: [];

if ($oldSummary !== '') {
    $history[] = ['text' => $oldSummary, 'date' => $now, 'trigger' => $trigger];
    // Max 10 history entries রাখো
    if (count($history) > 10) {
        $history = array_slice($history, -10);
    }
}

// ── Gemini call — নতুন summary বানাও ─────────────────────────────────────────
$gemResult = generateSummary($GEMINI_API_KEY, $traveler['name'], $docContext, $oldSummary);

if (!$gemResult['success']) {
    jsonOut(['success' => false, 'message' => 'Gemini error: ' . $gemResult['error']]);
}

$newSummary  = $gemResult['summary'];
$summaryInfo = $gemResult['summary_info'];

// ── travelers table update ────────────────────────────────────────────────────
$pdo->prepare("
    UPDATE travelers
    SET summary          = ?,
        history_summary  = ?,
        summary_info     = ?
    WHERE sys_id = ?
")->execute([
    $newSummary,
    json_encode($history, JSON_UNESCAPED_UNICODE),
    json_encode($summaryInfo, JSON_UNESCAPED_UNICODE),
    $travelerSysId,
]);

jsonOut([
    'success'       => true,
    'traveler_id'   => $travelerSysId,
    'summary'       => $newSummary,
    'history_count' => count($history),
    'summary_info'  => $summaryInfo,
    'doc_count'     => count($documents),
]);


// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Gemini/DB থেকে আসা possibly-nested/possibly-malformed JSON string থেকে
 * plain prose summary বের করে আনে। তিন স্তরে চেষ্টা করে:
 *   1. $parsed যদি ইতিমধ্যে decode হওয়া array হয় — nested {"summary":...}
 *      থাকলে recursively ভেতরে ঢুকে plain string বের করে আনে
 *   2. $parsed null হলে (json_decode fail করেছে, সম্ভবত output truncated/
 *      malformed ছিল) — raw string-এ regex দিয়ে "summary":"..." pattern
 *      খুঁজে বের করার চেষ্টা করে, nested হলে সেটাও আবার unwrap করে
 *   3. দুটোই fail করলে raw fallback string-ই রিটার্ন করে (শেষ উপায়)
 */
function extractPlainSummary($parsed, string $fallbackClean): string
{
    $value = $parsed;
    $depth = 0;

    while (is_array($value) && isset($value['summary']) && $depth < 5) {
        $value = $value['summary'];
        $depth++;

        if (is_string($value)) {
            $maybeJson = json_decode($value, true);
            if (is_array($maybeJson) && isset($maybeJson['summary'])) {
                $value = $maybeJson;
            } else {
                break;
            }
        }
    }

    if (is_string($value) && trim($value) !== '') {
        return trim($value);
    }

    // json_decode ব্যর্থ হলে (truncated/malformed JSON) regex fallback
    $text = $fallbackClean;
    for ($i = 0; $i < 5; $i++) {
        if (preg_match('/"summary"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $text, $m)) {
            $inner = stripcslashes($m[1]);
            if (preg_match('/^\s*\{.*"summary"\s*:/s', $inner)) {
                $text = $inner;
                continue;
            }
            return trim($inner);
        }
        break;
    }

    return trim($fallbackClean);
}

/**
 * Documents থেকে Gemini এর জন্য context string বানাও
 */
function buildDocumentContext(array $documents): string
{
    $lines = [];
    $today = strtotime(date('Y-m-d')); // date_default_timezone_set('Asia/Dhaka') আগেই সেট করা আছে

    foreach ($documents as $doc) {
        $line = "[{$doc['doc_type']}]";
        if ($doc['doc_number'])  $line .= " #{$doc['doc_number']}";

        // expiry_date শুধু raw তারিখ হিসেবে না, স্পষ্টভাবে EXPIRED/valid
        // status যোগ করে দেওয়া হচ্ছে — Gemini নিজে "আজকের তারিখ" জানে না,
        // তাই raw তারিখ দেখে expired কিনা বুঝতে পারে না
        if ($doc['expiry_date']) {
            $expTs = strtotime($doc['expiry_date']);
            if ($expTs) {
                $daysLeft = (int)floor(($expTs - $today) / 86400);
                if ($daysLeft < 0) {
                    $line .= " (expired on {$doc['expiry_date']}, " . abs($daysLeft) . " days ago — EXPIRED)";
                } elseif ($daysLeft <= 30) {
                    $line .= " (expires {$doc['expiry_date']}, only {$daysLeft} days left — EXPIRING SOON)";
                } else {
                    $line .= " (expires {$doc['expiry_date']}, valid)";
                }
            } else {
                $line .= " (expires {$doc['expiry_date']})";
            }
        }
        if ($doc['issue_date'])  $line .= " (issued {$doc['issue_date']})";
        $line .= ": " . trim($doc['summary']);
        $lines[] = $line;
    }
    return implode("\n", $lines);
}

/**
 * Gemini call — traveler living summary generate করো
 */
function generateSummary(
    string $apiKey,
    string $travelerName,
    string $docContext,
    string $oldSummary
): array {

    $model = 'gemini-2.5-flash';

    $existingPart = $oldSummary
        ? "=== EXISTING PROFILE ===\n{$oldSummary}\n\n"
        : "";

    $prompt = <<<PROMPT
You are maintaining a living traveler profile for a Bangladeshi travel agency CRM.

Traveler name: {$travelerName}

{$existingPart}=== DOCUMENTS ON FILE ===
{$docContext}

Task: Write a concise, factual, third-person profile narrative of this traveler based on all the documents above.
- Merge existing profile with new document information
- Keep everything still true, add what is new, correct anything superseded
- Include: identity, passport details, visa status, travel history, financial standing if known
- If any document is marked EXPIRED or EXPIRING SOON above, explicitly mention that in the summary (e.g. "passport expired on X" or "visa expiring in N days") — this is operationally important for the agency
- Do NOT invent facts. Use only what the documents state.
- Write in neutral third-person, 3-6 sentences.
- The "summary" value must be PLAIN prose text only — never JSON, never a nested {"summary": ...} object, even if EXISTING PROFILE above happens to look like one (that would be a data-formatting bug, not the intended shape — extract only the prose meaning from it)
- Return ONLY a JSON object:

{ "summary": "...the updated profile narrative as plain prose..." }
PROMPT;

    $payload = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'responseSchema'   => [
                'type'       => 'OBJECT',
                'properties' => [
                    'summary' => ['type' => 'STRING'],
                ],
                'required'   => ['summary'],
            ],
            'temperature'      => 0.2,
            'maxOutputTokens'  => 4096,
        ],
    ], JSON_UNESCAPED_UNICODE);

    $start = microtime(true);
    $ch    = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 90,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $elapsed = round(microtime(true) - $start, 2);

    if ($err)       return ['success' => false, 'error' => 'cURL: ' . $err];
    if ($code != 200) {
        $b = json_decode($raw, true);
        return ['success' => false, 'error' => $b['error']['message'] ?? "HTTP {$code}"];
    }

    $body       = json_decode($raw, true);
    $text       = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $tokenCount = (int)($body['usageMetadata']['totalTokenCount'] ?? 0);
    $finishReason = $body['candidates'][0]['finishReason'] ?? '';

    // Raw Gemini text log করো — finishReason দিয়ে বোঝা যাবে output
    // truncate হয়েছিল কিনা (MAX_TOKENS মানে output কেটে গেছে)
    error_log('[regenerate-summary] finishReason: ' . $finishReason);
    error_log('[regenerate-summary] raw text: ' . substr($text, 0, 800));

    $clean  = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text));
    $parsed = json_decode($clean, true);

    error_log('[regenerate-summary] parsed type: ' . gettype($parsed));

    $summary = extractPlainSummary($parsed, $clean);

    error_log('[regenerate-summary] final summary: ' . substr($summary, 0, 200));

    if (!$summary) {
        return ['success' => false, 'error' => 'Empty summary from Gemini'];
    }

    // Safety net: extraction এর পরও যদি ফলাফল JSON-এর মতো দেখায় (শুরুতে
    // { এবং তাতে "summary" শব্দ আছে), সেটা কখনোই UI-তে raw দেখানো ঠিক না —
    // বরং fail করে দেওয়া ভালো, যাতে অন্তত পুরনো summary অক্ষত থাকে এবং
    // ইউজার retry করতে পারে, dirty data DB-তে না জমে
    if (preg_match('/^\s*\{.*"summary"\s*:/s', $summary)) {
        error_log('[regenerate-summary] extraction still looks like JSON, failing safely');
        return ['success' => false, 'error' => 'Could not extract clean summary text (Gemini output may have been truncated — try again)'];
    }

    return [
        'success'      => true,
        'summary'      => $summary,
        'summary_info' => ['taken_token' => $tokenCount, 'time' => $elapsed],
    ];
}

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}