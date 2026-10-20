<?php
/**
 * FILE PATH: /api/umrah-groups/extract-ticket.php
 *
 * AI-powered airline ticket extraction for Umrah Group flights.
 * Extracts PNR, passengers, flight legs from ticket PDF/image.
 * Then matches passenger names against group travelers.
 *
 * POST (multipart):
 *   file            — ticket PDF / JPG / PNG
 *   group_sys_id    — to load travelers for matching
 *
 * Response:
 * {
 *   success: true,
 *   ticket: {
 *     pnr, airline, flight_no, class,
 *     legs: [{from, to, dep_date, dep_time, arr_date, arr_time}],
 *     raw_passengers: ["SAZID ASIF MOSTOFA", ...]
 *   },
 *   matched: [
 *     {traveler_sys_id, traveler_name, ticket_name, match: 'exact'|'fuzzy'|'none'}
 *   ],
 *   unmatched_ticket_names: ["RAHIM AHMED", ...],
 *   unmatched_travelers: [{traveler_sys_id, traveler_name}, ...]
 * }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../server/db_connection.php';

// ── Gemini key ──────────────────────────────────────────────
$GEMINI_API_KEY = '';
foreach ([
    __DIR__ . '/../../gemini-apikey.txt',
    __DIR__ . '/../../../gemini-apikey.txt',
] as $p) {
    if (file_exists($p)) { $GEMINI_API_KEY = trim(file_get_contents($p)); break; }
}
if (empty($GEMINI_API_KEY)) {
    echo json_encode(['success'=>false,'message'=>'Gemini API key not found']); exit;
}
$GEMINI_MODEL = trim(@file_get_contents(__DIR__.'/../../gemini-model.txt')) ?: 'gemini-2.5-flash';

// ── Validate upload ─────────────────────────────────────────
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'No file uploaded']); exit;
}
$groupSysId = trim($_POST['group_sys_id'] ?? '');
if (!$groupSysId) {
    echo json_encode(['success'=>false,'message'=>'group_sys_id required']); exit;
}

$file    = $_FILES['file'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf','jpg','jpeg','png','webp'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['success'=>false,'message'=>'Invalid file type. Use PDF, JPG or PNG']); exit;
}

$tmpPath = sys_get_temp_dir() . '/ticket_' . uniqid() . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
    echo json_encode(['success'=>false,'message'=>'Failed to save uploaded file']); exit;
}

// ── Load group travelers for matching ───────────────────────
$travStmt = $pdo->prepare("
    SELECT m.traveler_id AS sys_id, t.name
    FROM traveler_group_members m
    JOIN travelers t ON t.sys_id = m.traveler_id
    WHERE m.group_id = ?
    ORDER BY t.name ASC
");
$travStmt->execute([$groupSysId]);
$groupTravelers = $travStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Build ticket extraction prompt ──────────────────────────
function buildTicketPrompt(): string {
    return <<<'PROMPT'
Analyze this airline ticket document carefully. It may be a booking confirmation, e-ticket, or itinerary.

Extract ALL the following information. Return ONLY valid JSON, no markdown, no explanation.

{
  "pnr": "6-character booking reference/PNR code",
  "airline": "Airline name",
  "flight_number": "Flight number(s) e.g. BG001 or BG001/BG002",
  "class": "Economy or Business or First",
  "legs": [
    {
      "from": "3-letter IATA airport code",
      "to": "3-letter IATA airport code",
      "dep_date": "YYYY-MM-DD",
      "dep_time": "HH:MM",
      "arr_date": "YYYY-MM-DD",
      "arr_time": "HH:MM"
    }
  ],
  "passengers": [
    "SURNAME GIVEN NAME",
    "SURNAME GIVEN NAME"
  ]
}

Rules:
- legs array: one entry per flight segment (e.g. DAC-MED is one leg, MED-JED is another)
- passengers: list EVERY SINGLE passenger from ALL pages — do not stop early, check every page
  Names are printed as SURNAME/GIVEN_NAMES on airline tickets — keep that order exactly
- If multiple PNRs are on the document, use the first/main one
- dates must be YYYY-MM-DD format
- times must be HH:MM 24h format
- If a field is not found, use null
- IMPORTANT: This may be a group booking with many passengers spread across multiple pages.
  You MUST return ALL of them, not just the first few.
PROMPT;
}

// ── Gemini HTTP call helper ──────────────────────────────────
function geminiCall(string $apiKey, string $model, array $parts, int $maxTokens = 2000): array
{
    $payload = json_encode([
        'contents'        => [['parts' => $parts]],
        'generationConfig'=> ['temperature'=>0.1,'maxOutputTokens'=>$maxTokens],
    ], JSON_UNESCAPED_UNICODE);

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)       throw new Exception("Gemini request failed: $err");
    if ($code !== 200) {
        $b = json_decode($raw, true);
        throw new Exception($b['error']['message'] ?? "HTTP $code");
    }
    $resp = json_decode($raw, true);
    $text = trim($resp['candidates'][0]['content']['parts'][0]['text'] ?? '');
    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
    $text = preg_replace('/\s*```$/m', '', $text);
    return ['text' => $text, 'raw' => $raw];
}

// ── Extract page images from PDF ──────────────────────────────
function pdfToPageImages(string $pdfPath, int $from, int $to, int $dpi = 120): array
{
    $imagick = new Imagick();
    $imagick->setResolution($dpi, $dpi);
    $imagick->readImage($pdfPath);
    $total  = $imagick->getNumberImages();
    $to     = min($to, $total - 1);
    $images = [];
    for ($i = $from; $i <= $to; $i++) {
        $imagick->setIteratorIndex($i);
        $imagick->setImageFormat('png');
        $b64 = base64_encode($imagick->getImageBlob());
        if ($b64) $images[] = $b64;
    }
    $imagick->clear(); $imagick->destroy();
    return $images;
}

// ── Call Gemini — split strategy for large PDFs ───────────────
function callGeminiTicket(string $apiKey, string $model, string $tmpPath, string $ext): array
{
    if ($ext !== 'pdf') {
        // Single image — one call
        $mimeMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
        $mime    = $mimeMap[$ext] ?? 'image/jpeg';
        $parts   = [
            ['text' => buildTicketPrompt()],
            ['inline_data' => ['mime_type'=>$mime,'data'=>base64_encode(file_get_contents($tmpPath))]],
        ];
        $r    = geminiCall($apiKey, $model, $parts, 2000);
        $data = json_decode($r['text'], true);
        if (!is_array($data)) throw new Exception('AI returned invalid JSON');
        return $data;
    }

    if (!extension_loaded('imagick')) throw new Exception('Imagick required for PDF processing');

    // Count pages
    $counter = new Imagick();
    $counter->setResolution(72, 72);
    $counter->readImage($tmpPath);
    $totalPages = $counter->getNumberImages();
    $counter->clear(); $counter->destroy();

    // ── Call 1: first 2 pages → full flight info + first batch of passengers ──
    $page1Images = pdfToPageImages($tmpPath, 0, min(2, $totalPages - 1)); // first 3 pages for flight info
    $parts1 = [['text' => buildTicketPrompt()]];
    foreach ($page1Images as $b64) {
        $parts1[] = ['inline_data' => ['mime_type'=>'image/png','data'=>$b64]];
    }
    $r1   = geminiCall($apiKey, $model, $parts1, 3000);
    $data = json_decode($r1['text'], true);
    if (!is_array($data)) {
        error_log('[extract-ticket] raw text: ' . substr($r1['text'], 0, 800));
        // Try to extract JSON block from within the response
        if (preg_match('/\{[\s\S]+\}/m', $r1['text'], $m)) {
            $data = json_decode($m[0], true);
        }
        if (!is_array($data)) {
            throw new Exception('AI returned invalid JSON. Raw: ' . substr($r1['text'], 0, 300));
        }
    }

    // ── Call 2..N: remaining pages in batches of 3 → passengers only ──
    $allPassengers = $data['passengers'] ?? [];
    $passengerPrompt = 'List ALL passenger names from this airline ticket page(s). Return ONLY a JSON array of strings, nothing else. Format: SURNAME GIVEN_NAMES (surname first, exactly as printed). Example: ["SAZID ASIF MOSTOFA","HOQUE MD AZIZUL"]';

    for ($start = 3; $start < $totalPages; $start += 3) {
        $end        = min($start + 2, $totalPages - 1);
        $batchImages= pdfToPageImages($tmpPath, $start, $end);
        if (!$batchImages) continue;

        $partsN = [['text' => $passengerPrompt]];
        foreach ($batchImages as $b64) {
            $partsN[] = ['inline_data' => ['mime_type'=>'image/png','data'=>$b64]];
        }
        try {
            $rN     = geminiCall($apiKey, $model, $partsN, 1000);
            $names  = json_decode($rN['text'], true);
            if (is_array($names)) {
                $allPassengers = array_values(array_unique(array_merge($allPassengers, $names)));
            }
        } catch (Throwable $e) {
            error_log('[extract-ticket] batch pages '.$start.'-'.$end.': '.$e->getMessage());
        }
    }

    $data['passengers'] = $allPassengers;
    return $data;
}

// ── Name matching ───────────────────────────────────────────
function normalizeName(string $name): string {
    return strtolower(preg_replace('/[^a-z]/i', '', $name));
}

/**
 * Tickets store names as SURNAME GIVEN_NAMES (e.g. "SAZID ASIF MOSTOFA")
 * DB stores as GIVEN_NAMES SURNAME (e.g. "ASIF MOSTOFA SAZID")
 * So we also try reversing the ticket name words to match.
 */
function reverseNameWords(string $name): string {
    $words = array_filter(explode(' ', trim($name)));
    if (count($words) <= 1) return $name;
    // Move first word (surname) to end
    $surname = array_shift($words);
    $words[] = $surname;
    return implode(' ', $words);
}

function matchPassengers(array $ticketPassengers, array $groupTravelers): array
{
    $matched              = [];
    $unmatchedTicket      = [];
    $unmatchedTravelerIds = array_column($groupTravelers, 'sys_id');

    foreach ($ticketPassengers as $tName) {
        $tNorm        = normalizeName($tName);
        $tNormReversed= normalizeName(reverseNameWords($tName));
        $bestSysId    = null;
        $bestName     = null;
        $bestType     = 'none';

        foreach ($groupTravelers as $gt) {
            $gNorm = normalizeName($gt['name']);

            // Exact normalized match (original or reversed)
            if ($tNorm === $gNorm || $tNormReversed === $gNorm) {
                $bestSysId = $gt['sys_id']; $bestName = $gt['name']; $bestType = 'exact'; break;
            }

            // Word intersection — ticket words vs DB words (both orderings)
            $tWords  = array_values(array_filter(explode(' ', strtolower($tName))));
            $tWords2 = array_values(array_filter(explode(' ', strtolower(reverseNameWords($tName)))));
            $gWords  = array_values(array_filter(explode(' ', strtolower($gt['name']))));

            $common1 = array_intersect($tWords,  $gWords);
            $common2 = array_intersect($tWords2, $gWords);
            $best    = max(count($common1), count($common2));

            // Need at least 2 matching words and cover majority of name
            $minRequired = min(2, count($gWords));
            if ($best >= $minRequired && $best >= 2 && $bestType !== 'exact') {
                $bestSysId = $gt['sys_id']; $bestName = $gt['name']; $bestType = 'fuzzy';
            }
        }

        $matched[] = [
            'ticket_name'     => $tName,
            'traveler_sys_id' => $bestSysId,
            'traveler_name'   => $bestName,
            'match'           => $bestType,
        ];

        if ($bestSysId) {
            $unmatchedTravelerIds = array_filter($unmatchedTravelerIds, fn($id) => $id !== $bestSysId);
        } else {
            $unmatchedTicket[] = $tName;
        }
    }

    $unmatchedTravelers = array_values(array_filter($groupTravelers,
        fn($gt) => in_array($gt['sys_id'], array_values($unmatchedTravelerIds))
    ));

    return compact('matched', 'unmatchedTicket', 'unmatchedTravelers');
}

// ── Main ────────────────────────────────────────────────────
try {
    $ticketData = callGeminiTicket($GEMINI_API_KEY, $GEMINI_MODEL, $tmpPath, $ext);
    @unlink($tmpPath);

    $rawPassengers = array_filter(array_map('trim', $ticketData['passengers'] ?? []));
    $matchResult   = matchPassengers(array_values($rawPassengers), $groupTravelers);

    echo json_encode([
        'success' => true,
        'ticket'  => [
            'pnr'            => $ticketData['pnr']           ?? null,
            'airline'        => $ticketData['airline']        ?? null,
            'flight_number'  => $ticketData['flight_number']  ?? null,
            'class'          => $ticketData['class']          ?? 'Economy',
            'legs'           => $ticketData['legs']           ?? [],
            'raw_passengers' => array_values($rawPassengers),
        ],
        'matched'                 => $matchResult['matched'],
        'unmatched_ticket_names'  => $matchResult['unmatchedTicket'],
        'unmatched_travelers'     => $matchResult['unmatchedTravelers'],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    @unlink($tmpPath);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}