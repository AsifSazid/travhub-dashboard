<?php
/**
 * FILE PATH: /api/air-tickets/generate-from-notes.php
 *
 * Mind Board থেকে select করা note গুলো (text + image, multimodal) Gemini-কে
 * পাঠিয়ে Summary এবং/অথবা Quotation তৈরি করে। এটা শুধু **generate** করে —
 * ফলাফল caller-কে ফেরত পাঠায়, DB-তে কিছুই সেভ করে না (সেভ করার জন্য আলাদা
 * action আছে, যাতে ইউজার আগে review করে তারপর সিদ্ধান্ত নিতে পারে)।
 *
 * POST (JSON):
 *   {
 *     work_sys_id: string,
 *     note_sys_ids: string[],       // selected note গুলোর sys_id
 *     action: 'summary'|'quotation'|'both',
 *     quotation_type_hint: 'gds'|'soto'|null   // Regenerate করার সময় user
 *                                                // যদি জোর করে একটা type চায়
 *   }
 *
 * OUTPUT:
 *   { success, summary_text?, quotation? { type, ...GDS or SOTO fields... } }
 */

session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/smb_upload_handler.php';
require_once '../../server/safe_folder_name.php';
require_once __DIR__ . '/../../server/ai-gemini.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true) ?: [];

$workSysId   = trim($body['work_sys_id']   ?? '');
$noteSysIds  = $body['note_sys_ids']       ?? [];
$action      = trim($body['action']        ?? '');
$typeHint    = trim($body['quotation_type_hint'] ?? ''); // '' | 'gds' | 'soto'

if (!$workSysId)                     jsonOut(['success' => false, 'message' => 'work_sys_id প্রয়োজন']);
if (empty($noteSysIds))              jsonOut(['success' => false, 'message' => 'কমপক্ষে একটা note select করতে হবে']);
if (!in_array($action, ['summary','quotation','both'], true)) jsonOut(['success' => false, 'message' => 'অবৈধ action']);

try {
    // ── ধাপ ১: selected note গুলো DB থেকে আনো ────────────────────────────
    $placeholders = implode(',', array_fill(0, count($noteSysIds), '?'));
    $stmt = $pdo->prepare("
        SELECT sys_id, note_type, content, file_name, work_sys_id, service_slug, board_name
        FROM task_notes
        WHERE sys_id IN ($placeholders) AND work_sys_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([...$noteSysIds, $workSysId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$notes) jsonOut(['success' => false, 'message' => 'কোনো note পাওয়া যায়নি']);

    // ── ধাপ ২: image note গুলোর actual file SMB থেকে base64 করে আনো ─────
    // (text note গুলো শুধু content ব্যবহার করবে, file লাগবে না)
    $omv = null;
    $textParts  = [];
    $imageParts = []; // Gemini payload-এর জন্য {mime_type, data}

    foreach ($notes as $n) {
        if ($n['note_type'] === 'text') {
            $textParts[] = trim($n['content'] ?? '');
            continue;
        }

        if ($n['note_type'] === 'image' && $n['file_name']) {
            try {
                $ctx = _genNotesGetCtxByWork($pdo, $n['work_sys_id'], $n['service_slug'] ?? 'air_ticket', $n['board_name'] ?? 'mindboard');
                $base = smbBuildPath($ctx);
                if (!$omv) $omv = new OMV_SMB_Manager();

                $tmpLocal = sys_get_temp_dir() . '/genfromnotes_' . uniqid() . '_' . $n['file_name'];
                if ($omv->get_file("{$base}/{$n['file_name']}", $tmpLocal) && file_exists($tmpLocal)) {
                    $ext  = strtolower(pathinfo($n['file_name'], PATHINFO_EXTENSION));
                    $mime = match($ext) {
                        'png'  => 'image/png',
                        'webp' => 'image/webp',
                        'gif'  => 'image/gif',
                        default => 'image/jpeg',
                    };
                    $imageParts[] = [
                        'mime_type' => $mime,
                        'data'      => base64_encode(file_get_contents($tmpLocal)),
                    ];
                    unlink($tmpLocal);
                }
            } catch (Throwable $imgErr) {
                error_log('[generate-from-notes] image fetch failed for ' . $n['sys_id'] . ': ' . $imgErr->getMessage());
                // একটা image আনতে ব্যর্থ হলেও বাকি note নিয়ে এগিয়ে যাই — পুরো request fail করার দরকার নেই
            }
        }
        // audio/video/file/pdf_images — এই ফিচারের scope-এর বাইরে, স্কিপ
    }

    if (!$textParts && !$imageParts) {
        jsonOut(['success' => false, 'message' => 'Selected note গুলো থেকে কোনো ব্যবহারযোগ্য content পাওয়া যায়নি']);
    }

    $combinedText = implode("\n\n---\n\n", array_filter($textParts));

    // ── ধাপ ৩: Work-এর existing segment/pax data (শুধু quotation-এর জন্য context) ──
    $workContext = '';
    if ($action === 'quotation' || $action === 'both') {
        $atStmt = $pdo->prepare("SELECT segment_data FROM works WHERE sys_id = ? LIMIT 1");
        $atStmt->execute([$workSysId]);
        $segData = $atStmt->fetchColumn();
        if ($segData) {
            $parsed = json_decode($segData, true);
            $common = $parsed['common'] ?? [];
            if ($common) {
                $workContext = "\n\nKnown passenger counts for this work (use these unless the notes clearly say otherwise): "
                    . "Adults: " . ($common['pax_adult'] ?? 0) . ", "
                    . "Children: " . ($common['pax_child'] ?? 0) . ", "
                    . "Infants: " . ($common['pax_infant'] ?? 0);
            }
        }
    }

    $result = ['success' => true];

    // ── Summary generate ──────────────────────────────────────────────
    if ($action === 'summary' || $action === 'both') {
        $summaryResult = _genSummary($combinedText, $imageParts);
        if (!$summaryResult['success']) {
            jsonOut(['success' => false, 'message' => 'Summary generate ব্যর্থ: ' . $summaryResult['error']]);
        }
        $result['summary_text'] = $summaryResult['text'];
    }

    // ── Quotation generate ────────────────────────────────────────────
    if ($action === 'quotation' || $action === 'both') {
        $quotResult = _genQuotation($combinedText, $imageParts, $workContext, $typeHint);
        if (!$quotResult['success']) {
            jsonOut(['success' => false, 'message' => 'Quotation generate ব্যর্থ: ' . $quotResult['error']]);
        }
        $result['quotation'] = $quotResult['data'];
    }

    jsonOut($result);

} catch (Throwable $e) {
    error_log('[generate-from-notes] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

/**
 * api/works/notes.php-এর _wkNotesGetCtxByWork() এর সমান্তরাল — SMB path
 * resolve করার জন্য একই logic, কিন্তু এখানে আলাদা ফাইলে থাকায় নিজস্ব নামে
 * duplicate রাখা হয়েছে (notes.php require করলে তার নিজস্ব switch/action
 * logic-ও চলে আসত, যেটা এখানে অবাঞ্ছিত)।
 */
function _genNotesGetCtxByWork(PDO $pdo, string $workSysId, string $serviceSlug, string $boardName = 'mindboard'): array
{
    $stmt = $pdo->prepare("
        SELECT w.sys_id AS work_sys_id,
               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.sys_id')) AS client_sys_id,
               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.name'))   AS client_name
        FROM works w WHERE w.sys_id = ? LIMIT 1
    ");
    $stmt->execute([$workSysId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [
            'work_sys_id' => $workSysId, 'task_sys_id' => null,
            'client_sys_id' => null, 'client_name' => 'unknown',
            'service_slug' => $serviceSlug, 'sub_folder' => "notes/{$boardName}",
        ];
    }
    return [
        'work_sys_id' => $row['work_sys_id'], 'task_sys_id' => null,
        'client_sys_id' => $row['client_sys_id'] ?? null,
        'client_name' => $row['client_name'] ?? 'unknown',
        'service_slug' => $serviceSlug, 'sub_folder' => "notes/{$boardName}",
    ];
}

/**
 * Multi-part multimodal Gemini call — ai-gemini.php-এর geminiCallWithFile()
 * শুধু একটা ফাইল support করে, এখানে একাধিক image + text একসাথে লাগবে।
 */
function _genMultimodalCall(string $systemPrompt, string $userText, array $imageParts, int $maxTokens = 4096): array
{
    $apiKey = trim(@file_get_contents(__DIR__ . '/../../gemini-apikey.txt'));
    if (!$apiKey) return ['success' => false, 'error' => 'Gemini API key configured নেই'];

    $model = 'gemini-2.5-flash';
    $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $parts = [];
    if ($userText !== '') $parts[] = ['text' => $userText];
    foreach ($imageParts as $img) {
        $parts[] = ['inline_data' => ['mime_type' => $img['mime_type'], 'data' => $img['data']]];
    }
    if (!$parts) return ['success' => false, 'error' => 'পাঠানোর মতো কোনো content নেই'];

    $payload = [
        'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
        'contents'           => [['role' => 'user', 'parts' => $parts]],
        'generationConfig'   => [
            'maxOutputTokens'  => $maxTokens,
            'temperature'      => 0.2,
            'responseMimeType' => 'application/json',
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 45, // Cloudflare gateway-timeout এর নিচে রাখা — আগের session-এ শেখা lesson
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)       return ['success' => false, 'error' => 'cURL: ' . $err];
    if ($code !== 200) {
        $b = json_decode($raw, true);
        return ['success' => false, 'error' => $b['error']['message'] ?? "HTTP {$code}"];
    }

    $bodyResp = json_decode($raw, true);
    $text     = $bodyResp['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $finish   = $bodyResp['candidates'][0]['finishReason'] ?? '';

    if (trim($text) === '') {
        return ['success' => false, 'error' => "Empty response (finishReason: {$finish})"];
    }

    return ['success' => true, 'text' => trim($text)];
}

/**
 * Summary — plain narrative প্রত্যাশিত, JSON wrapper দিয়ে চেয়ে নিলে আগের
 * session-এ দেখা "double-JSON" bug এড়ানো যায় (traveler summary-তে একবার
 * এই সমস্যায় পড়েছিলাম — prompt-এ output structure literally লিখলে Gemini
 * সেটাকেই content ভেবে নকল করে ফেলে)।
 */
function _genSummary(string $combinedText, array $imageParts): array
{
    $system = <<<SYS
You are an Air Ticket Quotation Parser for a Bangladeshi travel agency. Your task is to extract and summarize flight requests from unstructured text, GDS terminal outputs (Amadeus/Sabre/Galileo), portal screenshots, or chat excerpts.
Requirements:
Extract: Origin/destination routes (IATA code or city name as visible), travel dates, flight timing (departure/arrival time, duration, layover/transit details if shown), passenger count/type, budget, specific airline/time preferences, transit details, and deadlines.
1. Write ONE short paragraph (2-4 sentences) covering the flight/route facts only: route, dates, airline, flight numbers, departure/arrival times, duration, and any transit/layover.
2. If multiple fare or baggage options exist, do NOT fold them into that paragraph. Instead, after the paragraph, list each option on its own line starting with "• ", in this shape: "• Option N (PRICE, Recommended if applicable): baggage allowance, refundability, change fee, meals, and any other flexibility detail for that option." Never merge two options' prices or terms into one line.
3. If only a single fare is mentioned, skip the bullet list and fold that one price into the paragraph instead.
4. Separate the paragraph and the bullet list with a single newline character (\n) between them, and a newline between each bullet.
5. Ensure currency codes or local airline terms (e.g., Biman, US-Bangla, Novoair) are handled accurately if present.
6. Output strictly as a valid, raw JSON object with no markdown formatting around it. The newlines inside "summary" must be actual \n escape sequences within the JSON string:
{
"summary": "..."
}
SYS;

    $userText = $combinedText !== '' ? "Notes:\n{$combinedText}" : "No text notes — see attached image(s) only.";

    $res = _genMultimodalCall($system, $userText, $imageParts, 2048);
    if (!$res['success']) return $res;

    // ⚠️ Gemini-কে summary-এর ভেতরে bullet list এর জন্য newline (paragraph
    // এর পরে "• Option N…" প্রতিটা নতুন লাইনে) দিতে বলা হয়েছে — কিন্তু
    // responseMimeType:application/json মোডেও Gemini মাঝেমধ্যে JSON string
    // value-এর ভেতরে literal/raw newline character বসিয়ে দেয় (escaped
    // "\n" এর বদলে), যেটা technically invalid JSON — json_decode() null
    // রিটার্ন করে, পুরো response silently ব্যর্থ হয়। _genSanitizeJsonText()
    // দিয়ে string-value-এর ভেতরের raw control character গুলো escape করে
    // নেওয়া হচ্ছে decode করার আগে।
    $parsed = json_decode(_genSanitizeJsonText($res['text']), true);
    $summary = is_array($parsed) ? ($parsed['summary'] ?? '') : '';

    // এক স্তর nested হয়ে গেলে (Gemini মাঝেমধ্যে করে) আরেকবার unwrap
    if (is_string($summary)) {
        $inner = json_decode(_genSanitizeJsonText($summary), true);
        if (is_array($inner) && isset($inner['summary'])) $summary = $inner['summary'];
    }

    $summary = trim((string)$summary);
    if ($summary === '') return ['success' => false, 'error' => 'Summary extract করা যায়নি Gemini output থেকে'];

    return ['success' => true, 'text' => $summary];
}

/**
 * Quotation — GDS বা SOTO, Gemini নিজে detect করবে। দুটো schema-ই prompt-এ
 * দেওয়া হচ্ছে, output-এ 'detected_type' ফিল্ড থাকবে যেটা caller ব্যবহার
 * করবে কোন ফর্ম (GDS/SOTO) prefill করতে হবে সেটা বুঝতে। $typeHint দেওয়া
 * থাকলে (Regenerate button থেকে) সেই type-ই জোর করে বলা হয়।
 */
function _genQuotation(string $combinedText, array $imageParts, string $workContext, string $typeHint): array
{
    $typeInstruction = $typeHint
        ? "The user has explicitly told you this is a **{$typeHint}** style quotation — use that schema regardless of what the notes look like."
        : "Decide whether this looks like structured GDS/airline-system data (flight codes, fare basis, PNR-style text) or a screenshot/casual price quote from an OTA/agent (a 'SOTO' style quotation). Set detected_type accordingly.";

    // ⚠️ একটা source (screenshot/text) এ একাধিক fare/baggage option থাকতে
    // পারে (যেমন booking-site এর "US$308 / US$309 / US$319" এর মতো ৩টা
    // card) — এগুলো আলাদা quotation না, একটাই quotation-এর soto.prices[]
    // array-তে একাধিক entry হিসেবে যাবে, প্রতিটার সাথে facility/flexibility
    // details (non-refundable, change fee, meals ইত্যাদি) আলাদা রাখা হয়।
    $system = <<<SYS
You are helping a Bangladeshi travel agency build an air ticket quotation from client conversation notes and/or screenshots.
{$typeInstruction}

Return ONLY a JSON object with this exact top-level shape:
{
  "detected_type": "gds" | "soto",
  "gds": {
    "airline": "",
    "segments": [ { "flight":"", "class":"", "date":"", "route":"", "departure":"", "arrival":"", "tag":"D1" } ],
    "fares": [ { "type":"ADT", "pax":1, "base_fare":0, "taxes":0, "gross_fare":0 } ]
  },
  "soto": {
    "trip_option": "One Way",
    "route": "",
    "airline": "",
    "class": "Economy",
    "pax_adult": 0, "pax_child": 0, "pax_infant": 0,
    "currency": "BDT",
    "segments": [ { "segment_title":"", "date":"", "airline":"", "flight_no":"", "dep_airport":"", "dep_time":"", "arr_airport":"", "arr_time":"" } ],
    "prices": [
      { "desc": "e.g. Checked 30kg baggage", "facility": "e.g. Non-refundable, Change fee 100 USD", "adult": 0, "child": 0, "infant": 0 }
    ],
    "refundable_status": "Refundable", "changeable_status": "Changeable"
  }
}
For "soto.prices": if the source shows multiple fare/baggage options (e.g. several price cards for the same route), create ONE entry per option in this array — do not merge them into a single price. Put the baggage allowance in "desc" and refundability/change-fee/meals/other flexibility details in "facility" for that same option. If only one price is shown, still return it as a single-item array.
For "soto.currency": use the 3-letter currency code as shown (e.g. "USD", "BDT", "AED"); default to "BDT" if not stated. Leave the "adult"/"child"/"infant" price numbers in that same original currency — do not convert them yourself.
Only fill the object matching detected_type with real data; leave the other one as its default empty shape.
Use only information stated in the notes/images. Do not invent flight numbers, prices, or dates that are not present.{$workContext}
SYS;

    $userText = $combinedText !== '' ? "Notes:\n{$combinedText}" : "No text notes — see attached image(s) only.";

    $res = _genMultimodalCall($system, $userText, $imageParts, 4096);
    if (!$res['success']) return $res;

    $parsed = json_decode($res['text'], true);
    if (!is_array($parsed) || empty($parsed['detected_type'])) {
        return ['success' => false, 'error' => 'Gemini output থেকে valid quotation structure পাওয়া যায়নি'];
    }

    return ['success' => true, 'data' => $parsed];
}

/**
 * Gemini responseMimeType:application/json থেকে আসা টেক্সট json_decode()
 * করার আগে sanitize করে — string value-এর ভেতরে থাকা raw (unescaped)
 * newline/tab/carriage-return কে valid JSON escape sequence-এ পরিণত করে।
 * এই control character গুলো JSON string-এর বাইরে (brace/bracket/comma-র
 * মাঝে) থাকলে সেগুলো harmless whitespace — শুধু string literal-এর
 * ভেতরেরগুলোই সমস্যা করে, তাই quote-tracking state machine দিয়ে শুধু
 * ভেতরেরগুলোই টার্গেট করা হচ্ছে, বাইরেরগুলো অক্ষত থাকে।
 */
function _genSanitizeJsonText(string $text): string
{
    $text = trim($text);
    $len = strlen($text);
    $out = '';
    $inString = false;
    $escaped = false;
    for ($i = 0; $i < $len; $i++) {
        $ch = $text[$i];
        if ($inString) {
            if ($escaped) {
                $out .= $ch;
                $escaped = false;
                continue;
            }
            if ($ch === '\\') { $out .= $ch; $escaped = true; continue; }
            if ($ch === '"') { $out .= $ch; $inString = false; continue; }
            if ($ch === "\n") { $out .= '\\n'; continue; }
            if ($ch === "\r") { $out .= '\\r'; continue; }
            if ($ch === "\t") { $out .= '\\t'; continue; }
            $out .= $ch;
        } else {
            if ($ch === '"') { $inString = true; }
            $out .= $ch;
        }
    }
    return $out;
}

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}