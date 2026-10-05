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
        $result['summary_text']       = $summaryResult['text'];
        $result['summary_structured'] = $summaryResult['structured'] ?? null;
    }

    // ── Quotation generate ────────────────────────────────────────────
    if ($action === 'quotation' || $action === 'both') {
        // typeHint='gds' → dedicated GDS prompt (always returns GDS, never SOTO)
        // typeHint='soto' → dedicated SOTO prompt (always returns SOTO, never GDS)
        // no hint → legacy auto-detect
        if ($typeHint === 'gds') {
            $quotResult = _genGdsQuotation($combinedText, $imageParts, $workContext);
        } elseif ($typeHint === 'soto') {
            $quotResult = _genSotoQuotation($combinedText, $imageParts, $workContext);
        } else {
            $quotResult = _genQuotation($combinedText, $imageParts, $workContext, '');
        }
        if (!$quotResult['success']) {
            jsonOut(['success' => false, 'message' => 'Quotation generate ব্যর্থ: ' . ($quotResult['error'] ?? 'Unknown error')]);
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
You are an air ticket expert for a Bangladeshi travel agency. Extract all flight information from the notes or screenshots and produce a structured summary that can later be used to generate EITHER a GDS-style quotation OR a SOTO (OTA) quotation.

Output strictly as a valid raw JSON object (no markdown) with this shape:
{
  "summary": "...",
  "structured": {
    "route": "",
    "trip_type": "One Way | Return | Multi-city",
    "airline": "",
    "pax": { "adult": 0, "child": 0, "infant": 0 },
    "currency": "BDT",
    "flights": [
      {
        "flight_no": "",
        "airline": "",
        "dep_iata": "",
        "arr_iata": "",
        "dep_time": "",
        "arr_time": "",
        "date": "",
        "class": ""
      }
    ],
    "fares": [
      {
        "label": "",
        "adult": 0,
        "child": 0,
        "infant": 0,
        "baggage": "",
        "refundable": "",
        "change_fee": ""
      }
    ]
  }
}

Rules:
- summary: 2-4 sentences covering route, dates, airline, flights, timings, pax. If multiple fare options exist, list each as "• Option N (PRICE): details" on separate lines after the paragraph.
- structured.route: IATA codes joined by "-" (e.g. "DAC-DXB" or "DAC-SIN-LGK-KUL-DAC")
- flights[].dep_time / arr_time: HH:MM format
- flights[].date: DD Mon YYYY
- fares[].label: short label (e.g. "Carry-on only", "30kg checked baggage")
- Use 0 for unknown prices. Leave currency as shown (BDT default).
- Use only data visible in source. Do not invent.
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

    $summary    = trim((string)$summary);
    $structured = is_array($parsed) ? ($parsed['structured'] ?? null) : null;
    if ($summary === '') return ['success' => false, 'error' => 'Summary extract করা যায়নি Gemini output থেকে'];

    return ['success' => true, 'text' => $summary, 'structured' => $structured];
}

/**
 * Quotation — GDS বা SOTO, Gemini নিজে detect করবে। দুটো schema-ই prompt-এ
 * দেওয়া হচ্ছে, output-এ 'detected_type' ফিল্ড থাকবে যেটা caller ব্যবহার
 * করবে কোন ফর্ম (GDS/SOTO) prefill করতে হবে সেটা বুঝতে। $typeHint দেওয়া
 * থাকলে (Regenerate button থেকে) সেই type-ই জোর করে বলা হয়।
 */
/**
 * GDS Quotation — user explicitly chose GDS button.
 * Always returns GDS schema. If input is a screenshot (no raw GDS text),
 * AI reconstructs Amadeus/Sabre style raw GDS AND parses it into segments+fares.
 */
function _genGdsQuotation(string $combinedText, array $imageParts, string $workContext): array
{
    $system = <<<SYS
You are a GDS (Global Distribution System) expert helping a Bangladeshi travel agency.
The user wants a GDS-style air ticket quotation from a screenshot or text notes.

Your EXACT steps:
STEP 1 — Read the source carefully. Extract every flight: airline code, flight number, booking class, date, departure IATA, arrival IATA, departure time, arrival time.
STEP 2 — Build raw_gds: reconstruct the standard GDS display text (Amadeus/Sabre style).
STEP 3 — Fill segments[]: one entry PER FLIGHT from step 1. NEVER leave segments empty if flights are visible.
STEP 4 — Fill fares[]: extract ADT/CHD/INF prices. If exact fare not shown, estimate from total or put 0. NEVER leave fares empty if a price is visible.
STEP 5 — Detect currency from source. Put fare amounts in ORIGINAL currency (do not convert).

Raw GDS format (for raw_gds field):
- Flight lines: " {N}  {AL}  {FLT}  {CLS} {DDMON} {D}  {DEP}  {ARR}  {HHMM}  {HHMM}"
  Example: " 1  BS          309  Y 19OCT M  DAC  SIN  0815  1425"
- Fare line: "BR1-9     1ADT    {AMOUNT}"
- Total: "TOTAL FARE - {CCY}    {AMOUNT}"

Return ONLY a JSON object (no markdown) with this exact shape:
{
  "detected_type": "gds",
  "gds": {
    "airline": "",
    "raw_gds": "",
    "currency": "BDT",
    "conversion_rate": 1,
    "segments": [
      { "flight": "", "class": "", "date": "", "route": "", "departure": "", "arrival": "", "tag": "D1" }
    ],
    "fares": [
      { "type": "ADT", "pax": 1, "base_fare": 0, "taxes": 0, "gross_fare": 0 }
    ]
  },
  "soto": { "trip_option":"", "route":"", "airline":"", "class":"", "pax_adult":0, "pax_child":0, "pax_infant":0, "currency":"BDT", "segments":[], "prices":[], "refundable_status":"", "changeable_status":"" }
}

Field rules:
- raw_gds: full GDS text, newlines as \n
- segments[].flight: airline code + number e.g. "BS309"
- segments[].route: "DAC-SIN"
- segments[].departure / arrival: "HH:MM", use "----" if unknown
- segments[].date: "19OCT" or "19 Oct 2026"
- segments[].tag: "D1","D2"... outbound; "R1","R2"... return
- airline: if single airline, use its name (e.g. "Biman Bangladesh"). If multi-airline route, use the first airline or write e.g. "BS / TR / AK / BG"
- fares[].type: "ADT" | "CNN" | "INF"
- fares[].pax: number of passengers of that type (default 1 for ADT if not stated)
- fares[].base_fare: the amount on the BR line (e.g. "BR1-4  1ADT  545.00" → base_fare=545). If no separate base shown, use gross as base.
- fares[].taxes: tax/YQ/surcharge amount if shown separately. If not shown, set 0.
- fares[].gross_fare: base_fare + taxes. If only a total is shown (e.g. TOTAL FARE 1090 for 2 pax), divide by pax count to get per-pax gross.
- currency: detect from source ("USD","AED","BDT" etc). Do NOT default to BDT if source shows another currency.
- conversion_rate: always 1 (user sets this manually)
- CRITICAL — NEVER return empty segments[] or empty fares[] if ANY flight or price info is visible in source.
- CRITICAL — fare amounts must be in the ORIGINAL source currency. Never convert.
- Do NOT invent flight numbers or prices not visible in source. Use 0 for unknown amounts.{$workContext}
SYS;

    $userText = $combinedText !== '' ? "Notes/GDS text:\n{$combinedText}" : "No text — extract from attached screenshot(s).";
    $res = _genMultimodalCall($system, $userText, $imageParts, 4096);
    if (!$res['success']) return $res;

    $rawText = $res['text'];
    // Strip markdown fences if Gemini wrapped in ```json ... ```
    $rawText = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
    $rawText = preg_replace('/\s*```$/', '', $rawText);

    $parsed = json_decode(_genSanitizeJsonText($rawText), true);

    // Gemini sometimes returns just the gds object directly (not nested)
    if (!is_array($parsed)) {
        error_log('[genGds] json_decode failed. raw: ' . substr($rawText, 0, 500));
        return ['success' => false, 'error' => 'GDS quotation structure পাওয়া যায়নি — AI invalid JSON দিয়েছে'];
    }

    // If AI returned the gds object directly (not wrapped in outer shape)
    if (isset($parsed['segments']) && !isset($parsed['gds'])) {
        $parsed = ['gds' => $parsed, 'soto' => []];
    }

    if (empty($parsed['gds'])) {
        error_log('[genGds] gds key missing or empty. keys: ' . implode(',', array_keys($parsed)));
        // Try to salvage — build minimal gds from whatever AI returned
        $parsed['gds'] = [
            'airline'  => $parsed['airline']  ?? '',
            'raw_gds'  => $parsed['raw_gds']  ?? '',
            'segments' => $parsed['segments'] ?? [],
            'fares'    => $parsed['fares']    ?? [],
        ];
    }

    $parsed['detected_type'] = 'gds';
    return ['success' => true, 'data' => $parsed];
}

/**
 * SOTO Quotation — user explicitly chose SOTO button.
 * Always returns SOTO schema from OTA screenshots / casual price quotes.
 */
function _genSotoQuotation(string $combinedText, array $imageParts, string $workContext): array
{
    // ⚠️ একটা source (screenshot/text) এ একাধিক fare/baggage option থাকতে
    // পারে — এগুলো soto.prices[] array-তে একাধিক entry হিসেবে যাবে।
    $system = <<<SYS
You are helping a Bangladeshi travel agency build an air ticket quotation from an OTA screenshot or casual price quote (called "SOTO" internally).

Return ONLY a JSON object (no markdown) with this exact shape:
{
  "detected_type": "soto",
  "gds": { "airline":"", "raw_gds":"", "segments":[], "fares":[] },
  "soto": {
    "trip_option": "One Way",
    "route": "",
    "airline": "",
    "class": "Economy",
    "pax_adult": 0, "pax_child": 0, "pax_infant": 0,
    "currency": "BDT",
    "segments": [
      { "segment_title":"", "date":"", "airline":"", "flight_no":"", "dep_airport":"", "dep_time":"", "arr_airport":"", "arr_time":"" }
    ],
    "prices": [
      { "desc": "e.g. Checked 30kg baggage", "facility": "e.g. Non-refundable, Change fee 100 USD", "adult": 0, "child": 0, "infant": 0 }
    ],
    "refundable_status": "Refundable",
    "changeable_status": "Changeable"
  }
}

Rules:
- trip_option: "One Way" | "Return" | "Multi-city"
- route: IATA codes joined by "-" (e.g. "DAC-DXB" or "DAC-SIN-LGK-KUL-DAC")
- prices: ONE entry per distinct fare/baggage option shown. Put baggage allowance in "desc", flexibility details (refund/change fee/meals) in "facility".
- currency: 3-letter code as shown; default BDT.
- Leave adult/child/infant prices in the original currency — do not convert.
- Use only information visible in the source. Do not invent data.{$workContext}
SYS;

    $userText = $combinedText !== '' ? "Notes:\n{$combinedText}" : "No text — extract from attached screenshot(s).";
    $res = _genMultimodalCall($system, $userText, $imageParts, 4096);
    if (!$res['success']) return $res;

    $parsed = json_decode(_genSanitizeJsonText($res['text']), true);
    if (!is_array($parsed) || empty($parsed['soto'])) {
        return ['success' => false, 'error' => 'SOTO quotation structure পাওয়া যায়নি'];
    }
    $parsed['detected_type'] = 'soto'; // force
    return ['success' => true, 'data' => $parsed];
}

/**
 * Legacy wrapper — called when no type hint (auto-detect).
 * Routes to GDS or SOTO based on typeHint, or tries to auto-detect.
 */
function _genQuotation(string $combinedText, array $imageParts, string $workContext, string $typeHint): array
{
    if ($typeHint === 'gds')  return _genGdsQuotation($combinedText, $imageParts, $workContext);
    if ($typeHint === 'soto') return _genSotoQuotation($combinedText, $imageParts, $workContext);

    // Auto-detect fallback (no button clicked — legacy path)
    $system = <<<SYS
You are helping a Bangladeshi travel agency. Look at the notes/screenshots and decide:
- If it looks like Amadeus/Sabre/Galileo GDS system output (flight codes, fare basis codes, PNR lines) → use GDS schema
- If it looks like an OTA/website screenshot or casual agent price quote → use SOTO schema

Return ONLY a JSON object with this exact shape:
{
  "detected_type": "gds" | "soto",
  "gds": { "airline":"", "raw_gds":"", "segments":[], "fares":[] },
  "soto": { "trip_option":"", "route":"", "airline":"", "class":"Economy", "pax_adult":0, "pax_child":0, "pax_infant":0, "currency":"BDT", "segments":[], "prices":[], "refundable_status":"", "changeable_status":"" }
}
Fill only the matching schema. Use only data present in the source.{$workContext}
SYS;
    $userText = $combinedText !== '' ? "Notes:\n{$combinedText}" : "No text — see attached image(s).";
    $res = _genMultimodalCall($system, $userText, $imageParts, 4096);
    if (!$res['success']) return $res;
    $parsed = json_decode(_genSanitizeJsonText($res['text']), true);
    if (!is_array($parsed) || empty($parsed['detected_type'])) {
        return ['success' => false, 'error' => 'Quotation structure পাওয়া যায়নি'];
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
/**
 * Robustly extract and clean JSON from Gemini output.
 * Handles: ```json fences, text before/after JSON,
 * unescaped newlines in strings, and truncated JSON.
 */
function _genSanitizeJsonText(string $text): string
{
    $text = trim($text);

    // 1. Strip ```json...``` or ```...``` fences
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $text, $m)) {
        $text = trim($m[1]);
    }

    // 2. Extract first JSON object/array if there is surrounding text
    if (strlen($text) > 0 && $text[0] !== '{' && $text[0] !== '[') {
        $posObj = strpos($text, '{');
        $posArr = strpos($text, '[');
        if ($posObj === false) $start = $posArr;
        elseif ($posArr === false) $start = $posObj;
        else $start = min($posObj, $posArr);
        if ($start !== false) $text = substr($text, $start);
    }

    // 3. Escape unescaped control chars inside string values
    $out = ''; $inStr = false; $esc = false;
    $len = strlen($text);
    for ($i = 0; $i < $len; $i++) {
        $ch = $text[$i];
        if ($inStr) {
            if ($esc)          { $out .= $ch; $esc = false; continue; }
            if ($ch === '\\') { $out .= $ch; $esc = true;  continue; }
            if ($ch === '"')   { $out .= $ch; $inStr = false; continue; }
            if ($ch === "\n") { $out .= '\\n'; continue; }
            if ($ch === "\r") { $out .= '\\r'; continue; }
            if ($ch === "\t") { $out .= '\\t'; continue; }
            $out .= $ch;
        } else {
            if ($ch === '"') $inStr = true;
            $out .= $ch;
        }
    }

    // 4. If still invalid JSON, try to close open structures (truncation fix)
    if (json_decode($out, true) === null) {
        $fixed = rtrim($out, ", \t\r\n");
        // Close unclosed string
        $inS = false; $es = false;
        $fl = strlen($fixed);
        for ($i = 0; $i < $fl; $i++) {
            $c = $fixed[$i];
            if ($es)               { $es = false; continue; }
            if ($inS && $c==='\\') { $es = true; continue; }
            if ($c === '"')         { $inS = !$inS; }
        }
        if ($inS) $fixed .= '"';
        // Close open brackets/braces
        $stack = []; $inS2 = false; $es2 = false;
        $fl2 = strlen($fixed);
        for ($i = 0; $i < $fl2; $i++) {
            $c = $fixed[$i];
            if ($es2)                { $es2 = false; continue; }
            if ($inS2 && $c === '\\') { $es2 = true; continue; }
            if ($c === '"')           { $inS2 = !$inS2; continue; }
            if ($inS2) continue;
            if ($c === '{')      $stack[] = '}';
            elseif ($c === '[')  $stack[] = ']';
            elseif ($c==='}' || $c===']') array_pop($stack);
        }
        while ($stack) $fixed .= array_pop($stack);
        if (json_decode($fixed, true) !== null) return $fixed;
    }

    return $out;
}

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}