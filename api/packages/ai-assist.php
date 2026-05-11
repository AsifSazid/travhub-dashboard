<?php
// api/packages/ai-assist.php
// POST { "action": "description"|"rating", "package": {...} }
// Calls Gemini API using key from root gemini-apykey.txt

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim($input['action'] ?? '');
$pkg    = $input['package']    ?? [];

if (!in_array($action, ['description', 'rating'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// ── Read Gemini API key ───────────────────────────────────────────────
$GEMINI_API_KEY = trim(file_get_contents('../../gemini-apikey.txt'));
$apiKey = $GEMINI_API_KEY;
$model = 'gemini-2.0-flash-lite';

if (!$apiKey) {
    echo json_encode(['success' => false, 'message' => 'Gemini API key not found (gemini-apykey.txt)']);
    exit;
}

// ── Build prompt ──────────────────────────────────────────────────────
$pkgSummary = implode("\n", array_filter([
    "Title: "       . ($pkg['title']       ?? ''),
    "Destination: " . ($pkg['countries']   ?? '') . ($pkg['cities'] ? " / " . $pkg['cities'] : ''),
    "Duration: "    . ($pkg['duration']    ?? ''),
    "Dates: "       . ($pkg['start_date']  ?? '') . " to " . ($pkg['end_date'] ?? ''),
    "Pax: "         . json_encode($pkg['pax'] ?? []),
    "Days: "        . ($pkg['days']        ?? 0),
    "Activities: "  . implode(', ', $pkg['activities'] ?? []),
    "Transfers: "   . implode(', ', $pkg['transfers']  ?? []),
    "Flights: "     . implode(', ', $pkg['flights']    ?? []),
    "Inclusions: "  . implode(', ', $pkg['inclusions'] ?? []),
    "Exclusions: "  . implode(', ', $pkg['exclusions'] ?? []),
    "Price: "       . ($pkg['overall_price'] ?? '') . " " . ($pkg['currency'] ?? ''),
]));

if ($action === 'description') {
    $prompt = "You are a professional travel copywriter for a Bangladeshi travel agency called TravHub.\n\n"
        . "Write a compelling, client-facing package description (150-200 words) for the following travel package.\n"
        . "The description should be engaging, highlight key experiences, and be written in clear English.\n"
        . "Do NOT include any markdown formatting, bullet points, or headings — just flowing paragraphs.\n\n"
        . "Package details:\n{$pkgSummary}\n\n"
        . "Write only the description text, nothing else.";
} else {
    $prompt = "You are a travel industry expert evaluating a travel package for TravHub, a Bangladeshi travel agency.\n\n"
        . "Rate the following package from 1 to 5 stars based on: destination appeal, itinerary quality, value, and completeness.\n\n"
        . "Package details:\n{$pkgSummary}\n\n"
        . "Respond with ONLY a valid JSON object in this exact format (no markdown, no extra text):\n"
        . '{"rating": 4, "reason": "Brief one-sentence explanation of the rating."}';
}

// ── Call Gemini API ───────────────────────────────────────────────────
$url     = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature'     => 0.7,
        'maxOutputTokens' => $action === 'description' ? 400 : 150,
    ],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response) {
    echo json_encode(['success' => false, 'message' => 'Gemini API request failed']);
    exit;
}

$data = json_decode($response, true);
if ($httpCode !== 200) {
    $errMsg = $data['error']['message'] ?? 'Gemini API error ' . $httpCode;
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
if (!$text) {
    echo json_encode(['success' => false, 'message' => 'Empty response from Gemini']);
    exit;
}

// ── Parse response ────────────────────────────────────────────────────
if ($action === 'description') {
    echo json_encode(['success' => true, 'text' => trim($text)]);
} else {
    // Rating — parse JSON from response
    $clean = preg_replace('/```json|```/i', '', $text);
    $clean = trim($clean);
    $rated = json_decode($clean, true);
    if (!$rated || !isset($rated['rating'])) {
        // Fallback: extract number from text
        preg_match('/\b([1-5])\b/', $clean, $m);
        $rated = ['rating' => (int)($m[1] ?? 3), 'reason' => trim(strip_tags($clean))];
    }
    echo json_encode([
        'success' => true,
        'rating'  => max(1, min(5, (int)$rated['rating'])),
        'reason'  => $rated['reason'] ?? '',
    ]);
}