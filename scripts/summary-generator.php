<?php
/**
 * TravHub Smart Upload v3 — Traveler Summary Generator
 * ====================================================
 * Heart of the traveler-level summary system.
 *
 * Workflow:
 *   1. Gather ALL context for a traveler (basic info, passport, NID, all
 *      active documents with their summaries + doc_data, previous summary)
 *   2. Send to Gemini with structured-output prompt
 *   3. Build the new summary JSON wrapper with meta_data
 *   4. Push old summary → previous_summary[] (newest first)
 *   5. Compress entries older than 6 months (keep only headline)
 *   6. Return both new + previous_summary ready for UPDATE
 *
 * This file is a pure library — no echo, no header. Called by:
 *   - api/travelers/regenerate-summary.php
 *
 * Returns:
 *   [
 *     'success'         => bool,
 *     'summary'         => string (JSON to UPDATE travelers.summary),
 *     'previous_summary'=> string (JSON to UPDATE travelers.previous_summary),
 *     'tokens_used'     => int,
 *     'error'           => string|null,
 *   ]
 */

require_once __DIR__ . '/gen_meta_for_summery.php';


/**
 * Regenerate a traveler's summary. Main entry point.
 *
 * @param  PDO     $pdo
 * @param  int     $travelerId          travelers.id
 * @param  string  $actor               User name or 'system'
 * @param  string  $triggerType         'document_upload' | 'manual'
 * @param  string  $informationUpdatedFor  Human-readable description, e.g. "Uploaded UAE visa"
 * @return array
 */
function regenerateTravelerSummary($pdo, $travelerId, $actor, $triggerType, $informationUpdatedFor) {
    $apiKey = trim(@file_get_contents(__DIR__ . '/../gemini-apikey.txt'));
    if (empty($apiKey)) {
        return failResult('Gemini API key not configured');
    }

    // ---- 1. Fetch traveler + existing summary state -----------------------
    $stmt = $pdo->prepare("
        SELECT id, sys_id, name, date_of_birth, phone, email, address, status,
               passport_no, nid_no, passport_info, nid_info,
               summary, previous_summary
        FROM travelers
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$travelerId]);
    $traveler = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$traveler) {
        return failResult('Traveler not found');
    }

    // ---- 2. Fetch all active documents ------------------------------------
    $docStmt = $pdo->prepare("
        SELECT sys_id, doc_type, doc_subtype, doc_number, passport_status,
               summary, doc_data, key_fields, language,
               country, validity_from, validity_to, linked_passport_number,
               issue_date, expiry_date, page_count, verification_status,
               created_at
        FROM traveler_documents
        WHERE traveler_id = ? AND status = 'active'
        ORDER BY
            CASE doc_type
                WHEN 'passport' THEN 1
                WHEN 'nid' THEN 2
                WHEN 'visa' THEN 3
                WHEN 'air_ticket' THEN 4
                WHEN 'hotel_voucher' THEN 5
                ELSE 99
            END,
            created_at DESC
    ");
    $docStmt->execute([$travelerId]);
    $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- 3. Read current summary for continuity ---------------------------
    $currentSummary = !empty($traveler['summary']) ? json_decode($traveler['summary'], true) : null;
    $prevSummaryHeadline = $currentSummary['summary_text']['headline'] ?? null;

    // ---- 4. Build prompt + call Gemini ------------------------------------
    $context = buildSummaryContext($traveler, $documents, $prevSummaryHeadline);
    $geminiResult = callGeminiForSummary($apiKey, $context, $informationUpdatedFor);

    if (!$geminiResult['success']) {
        return failResult($geminiResult['error']);
    }

    $summaryText = $geminiResult['summary_text'];
    $tokensUsed  = $geminiResult['tokens_used'];

    // ---- 5. Build new summary wrapper -------------------------------------
    $newCount = ($currentSummary['summary_count'] ?? 0) + 1;

    // meta_data: if first-time, build; else append
    if ($currentSummary === null) {
        $newMetaJson = buildSummaryMeta($actor, $triggerType, $tokensUsed);
    } else {
        $existingMeta = isset($currentSummary['meta_data'])
            ? json_encode($currentSummary['meta_data'])
            : null;
        $newMetaJson = appendSummaryMeta($existingMeta, $actor, $triggerType, $tokensUsed);
    }

    $newSummary = [
        'summary_count'           => $newCount,
        'date'                    => date('d-m-Y'),
        'information_updated_for' => $informationUpdatedFor,
        'summary_text'            => $summaryText,
        'meta_data'               => json_decode($newMetaJson, true),
    ];

    // ---- 6. Push old summary → previous_summary, compress old entries -----
    $newPreviousSummary = updatePreviousSummary(
        $traveler['previous_summary'],
        $currentSummary
    );

    return [
        'success'          => true,
        'summary'          => json_encode($newSummary, JSON_UNESCAPED_UNICODE),
        'previous_summary' => json_encode($newPreviousSummary, JSON_UNESCAPED_UNICODE),
        'tokens_used'      => $tokensUsed,
        'error'            => null,
    ];
}


// ============================================================================
// Context building
// ============================================================================

function buildSummaryContext($traveler, $documents, $prevSummaryHeadline) {
    // Strip down each document to what Gemini actually needs (avoid sending
    // huge ocr_text or oversized JSON for every doc).
    $compactDocs = [];
    foreach ($documents as $doc) {
        $compact = [
            'doc_type'        => $doc['doc_type'],
            'doc_subtype'     => $doc['doc_subtype'],
            'doc_number'      => $doc['doc_number'],
            'passport_status' => $doc['passport_status'],
            'summary'         => $doc['summary'],
            'country'         => $doc['country'],
            'validity_from'   => $doc['validity_from'],
            'validity_to'     => $doc['validity_to'],
            'issue_date'      => $doc['issue_date'],
            'expiry_date'     => $doc['expiry_date'],
            'page_count'      => (int)$doc['page_count'],
            'verified'        => $doc['verification_status'] === 'verified',
            'uploaded_at'     => $doc['created_at'],
        ];
        // Inline structured doc_data if small enough
        if (!empty($doc['doc_data'])) {
            $compact['doc_data'] = json_decode($doc['doc_data'], true);
        } elseif (!empty($doc['key_fields'])) {
            $compact['key_fields'] = json_decode($doc['key_fields'], true);
        }
        $compactDocs[] = $compact;
    }

    return [
        'traveler' => [
            'name'           => $traveler['name'],
            'date_of_birth'  => $traveler['date_of_birth'],
            'phone'          => $traveler['phone'],
            'email'          => $traveler['email'],
            'address'        => $traveler['address'],
            'passport_no'    => $traveler['passport_no'],
            'nid_no'         => $traveler['nid_no'],
            'passport_info'  => !empty($traveler['passport_info'])
                                  ? json_decode($traveler['passport_info'], true) : null,
            'nid_info'       => !empty($traveler['nid_info'])
                                  ? json_decode($traveler['nid_info'], true) : null,
        ],
        'documents'             => $compactDocs,
        'previous_headline'     => $prevSummaryHeadline,
        'today'                 => date('Y-m-d'),
    ];
}


// ============================================================================
// Gemini call
// ============================================================================

function callGeminiForSummary($apiKey, $context, $informationUpdatedFor) {
    $prompt = buildSummaryPrompt($context, $informationUpdatedFor);

    $payload = [
        'contents' => [
            ['parts' => [['text' => $prompt]]],
        ],
        'generationConfig' => [
            'response_mime_type' => 'application/json',
            'temperature'        => 0.2,
            'maxOutputTokens'    => 4096,
        ],
    ];

    $model = 'gemini-2.0-flash';
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => 'Curl error: ' . $err];
    }
    curl_close($ch);

    if ($httpCode !== 200) {
        $data = json_decode($response, true);
        $msg = $data['error']['message'] ?? "HTTP {$httpCode}";
        return ['success' => false, 'error' => 'Gemini: ' . $msg];
    }

    $result = json_decode($response, true);
    $text   = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if ($text === '') {
        return ['success' => false, 'error' => 'Empty response from Gemini'];
    }

    // Strip code fences if present
    $clean = trim(preg_replace('/^```json\s*|\s*```$/m', '', $text));
    $summaryText = json_decode($clean, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'JSON parse: ' . json_last_error_msg()];
    }

    // Normalize: ensure all 5 expected fields exist
    $summaryText = normalizeSummaryText($summaryText);

    // Token usage
    $tokensUsed = ($result['usageMetadata']['promptTokenCount'] ?? 0)
                + ($result['usageMetadata']['candidatesTokenCount'] ?? 0);

    return [
        'success'      => true,
        'summary_text' => $summaryText,
        'tokens_used'  => $tokensUsed,
    ];
}


function buildSummaryPrompt($context, $informationUpdatedFor) {
    $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $previousLine = $context['previous_headline']
        ? "Previous summary headline (for continuity, do not just repeat it):\n  \"{$context['previous_headline']}\""
        : "This is the first summary for this traveler.";

    return <<<PROMPT
You are generating a comprehensive profile summary for a traveler at a Bangladeshi travel agency.

CONTEXT (current state of this traveler):
{$contextJson}

TRIGGER: {$informationUpdatedFor}

{$previousLine}

Generate a JSON object describing the traveler in this EXACT shape (return ONLY the JSON, no markdown):

{
    "headline": "ONE sentence (~25 words) elevator pitch. Include name, age, nationality, profession if known, and the single most important fact about their travel status. Example: 'Asif M Sazid is a 35-year-old Bangladeshi software engineer with valid passport, active UAE visa, and history of 3 international trips in 2025-26.'",

    "current_status": {
        "passport": "Status of current passport. Format: 'Valid until DD-MM-YYYY' or 'Expired DD-MM-YYYY' or 'No passport on file'",
        "active_visas": ["Array of active visas. Each entry: 'Country (until DD-MM-YYYY)'. Empty array if none."],
        "upcoming_trips": ["Array of upcoming trips from air_ticket/hotel_voucher docs. Each entry: 'Destination, departing DD-MM-YYYY'. Empty array if none."]
    },

    "highlights": [
        "3-6 short bullet points covering the most notable facts: completed trips, financial standing from bank statements, employment status, education, family information, recent activities. Each bullet ~15 words."
    ],

    "recent_activity": "ONE sentence describing the most recent change/update. Reference TRIGGER above.",

    "details": "A flowing 2-4 paragraph narrative that humans will read. Cover: who the traveler is, their travel history, current standing, notable documents on file, and anything an agent should know when working with them. Use natural prose, not bullets."
}

RULES:
- All date formats in the output: DD-MM-YYYY
- If a field has no data, use empty string "" or empty array [] — never null
- Do not invent facts not in the context
- For the "details" field, write 200-400 words of natural English prose
- Bengali names in context should be retained in their original script if present; otherwise use English transliterations
PROMPT;
}


function normalizeSummaryText($raw) {
    return [
        'headline'        => $raw['headline'] ?? '',
        'current_status'  => [
            'passport'       => $raw['current_status']['passport'] ?? '',
            'active_visas'   => $raw['current_status']['active_visas'] ?? [],
            'upcoming_trips' => $raw['current_status']['upcoming_trips'] ?? [],
        ],
        'highlights'      => $raw['highlights'] ?? [],
        'recent_activity' => $raw['recent_activity'] ?? '',
        'details'         => $raw['details'] ?? '',
    ];
}


// ============================================================================
// previous_summary update + compression
// ============================================================================

/**
 * Pushes the current summary into the previous_summary array (newest first),
 * then compresses entries older than 6 months down to {summary_count, date,
 * information_updated_for, summary_text: {headline}} only.
 *
 * @param  string|null  $existingPreviousJson  Current previous_summary JSON
 * @param  array|null   $oldSummary            The summary that's being replaced
 * @return array                               New previous_summary array
 */
function updatePreviousSummary($existingPreviousJson, $oldSummary) {
    $previous = !empty($existingPreviousJson) ? json_decode($existingPreviousJson, true) : [];
    if (!is_array($previous)) $previous = [];

    // Push old summary to top (newest first)
    if (is_array($oldSummary) && !empty($oldSummary)) {
        array_unshift($previous, $oldSummary);
    }

    // Compress entries older than 6 months
    $cutoffTimestamp = strtotime('-6 months');
    foreach ($previous as &$entry) {
        if (!isset($entry['date'])) continue;
        $entryTs = parseDdMmYyyy($entry['date']);
        if ($entryTs && $entryTs < $cutoffTimestamp) {
            // Compress: keep only count, date, trigger reason, headline
            $headline = $entry['summary_text']['headline'] ?? '';
            $entry = [
                'summary_count'           => $entry['summary_count'] ?? null,
                'date'                    => $entry['date'],
                'information_updated_for' => $entry['information_updated_for'] ?? '',
                'summary_text'            => ['headline' => $headline],
                'compressed'              => true,
            ];
        }
    }
    unset($entry);

    return $previous;
}


function parseDdMmYyyy($dateStr) {
    // Accepts DD-MM-YYYY
    if (!preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $dateStr, $m)) {
        return null;
    }
    return mktime(0, 0, 0, (int)$m[2], (int)$m[1], (int)$m[3]);
}


// ============================================================================
// Helpers
// ============================================================================

function failResult($error) {
    return [
        'success'          => false,
        'summary'          => null,
        'previous_summary' => null,
        'tokens_used'      => 0,
        'error'            => $error,
    ];
}