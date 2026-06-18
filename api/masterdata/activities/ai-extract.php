<?php
/**
 * api/masterdata/activities/ai-extract.php
 * POST { 
 *   text?         → plain text
 *   image_base64? + image_mime? → uploaded image
 *   image_url?    → image link
 *   pdf_base64?   → PDF upload
 *   youtube_url?  → YouTube link
 * }
 * Returns { success, activity:{...}, variants:[...] }
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/ai-gemini.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']); exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];

// ── Build Gemini content parts ──────────────────────────────────────────
$parts = [];

$text        = trim($in['text']        ?? '');
$imageB64    = trim($in['image_base64'] ?? '');
$imageMime   = trim($in['image_mime']   ?? 'image/jpeg');
$imageUrl    = trim($in['image_url']    ?? '');
$pdfB64      = trim($in['pdf_base64']  ?? '');
$youtubeUrl  = trim($in['youtube_url'] ?? '');

if (!$text && !$imageB64 && !$imageUrl && !$pdfB64 && !$youtubeUrl) {
    echo json_encode(['success' => false, 'message' => 'No input provided']); exit;
}

// YouTube URL — Gemini 2.5 supports natively via fileUri
if ($youtubeUrl) {
    $parts[] = ['fileData' => ['mimeType' => 'video/mp4', 'fileUri' => $youtubeUrl]];
    $parts[] = ['text' => "Extract activity information from this video." . ($text ? " Additional context: {$text}" : '')];
    // $parts[] = ['text' => "Analyze this YouTube video: " . $youtubeUrl];
    // $parts[] = ['text' => "Extract activity information from this video." . ($text ? " Additional context: {$text}" : '')];
} else {
    if ($imageB64) {
        $parts[] = ['inlineData' => ['mimeType' => $imageMime, 'data' => $imageB64]];
    }
    if ($imageUrl) {
        $parts[] = ['fileData' => ['mimeType' => 'image/jpeg', 'fileUri' => $imageUrl]];
    }
    if ($pdfB64) {
        $parts[] = ['inlineData' => ['mimeType' => 'application/pdf', 'data' => $pdfB64]];
    }
    if ($text) {
        $parts[] = ['text' => $text];
    }
}

// ── System prompt ───────────────────────────────────────────────────────
$system = <<<SYS
You are a travel activity data extractor for a travel agency management system.
Extract structured activity information and return ONLY valid JSON — no explanation, no markdown.

RULES:
- Extract as much as possible from the input
- description_points: break the activity into time-sequenced steps/highlights if possible. Each point has time (e.g. "09:00 AM"), duration (e.g. "2 hours"), description (HTML string with <p>, <b>, <ul> allowed)
- variants: each distinct pricing option is a separate variant (e.g. "SIC", "Private", "With Lunch", different transport types)
- If a field is not found, use null or empty string/array — never guess prices
- operating_days: comma-separated from: mon,tue,wed,thu,fri,sat,sun — use "mon,tue,wed,thu,fri,sat,sun" if not specified
- tags: pick relevant ones from: beach,family,adventure,luxury,honeymoon,cultural,sightseeing,nature,water_sports,theme_park,nightlife,shopping,religious,wildlife,city_tour
- unstructured_data: any extra info that doesn't fit the schema (e.g. dress_code, meeting_instructions, special_notes, booking_ref_format) — store as key-value pairs
- variant unstructured_data: pickup_time, drop_time, special_requirement, booking_note, etc.
- currency_code: use the country's local currency if detectable, else leave empty
- sell_price: only fill if explicitly mentioned, else 0
- net_cost: always 0 (internal cost unknown from marketing material)
SYS;

$userPrompt = <<<USR
Extract activity data and return this exact JSON structure:

{
  "activity": {
    "name": "",
    "type": "tour",
    "category": "",
    "duration_hours": 0,
    "duration_typical": "",
    "start_time": "",
    "end_time": "",
    "operating_days": "mon,tue,wed,thu,fri,sat,sun",
    "booking_lead_days": null,
    "popularity": 3,
    "pickup_from_city": [],
    "dropoff_city": [],
    "tags": [],
    "description_points": [
      {"time": "", "duration": "", "description": ""}
    ],
    "images": [],
    "min_pax": null,
    "max_pax": null,
    "age_min": null,
    "languages": "",
    "meeting_point": "",
    "inclusions": [],
    "exclusions": [],
    "cancellation_policy": "",
    "search_terms": "",
    "location": "",
    "highlights": "",
    "unstructured_data": {}
  },
  "variants": [
    {
      "variant_name": "",
      "price_basis": "per_pax",
      "currency_code": "",
      "net_cost": 0,
      "sell_price": 0,
      "markup_type": "percent",
      "markup_value": 0,
      "child_price": null,
      "transport_mode": "none",
      "capacity_min": null,
      "capacity_max": null,
      "meal_breakfast": false,
      "meal_lunch": false,
      "meal_dinner": false,
      "ticket_included": false,
      "guide_included": false,
      "guide_language": "",
      "unstructured_data": {}
    }
  ]
}

If no distinct variants found, create one default variant named after the activity type (e.g. "Standard Tour").
USR;

// ── API call ────────────────────────────────────────────────────────────
$apiKey = trim(@file_get_contents(__DIR__ . '/../../../gemini-apikey.txt') ?: '');
if (!$apiKey) {
    echo json_encode(['success' => false, 'message' => 'Gemini API key not configured']); exit;
}

$parts[] = ['text' => $userPrompt];

$payload = json_encode([
    'system_instruction' => ['parts' => [['text' => $system]]],
    'contents'           => [['role' => 'user', 'parts' => $parts]],
    'generationConfig'   => [
        'maxOutputTokens'    => 4000,
        'temperature'        => 0.2,
        'response_mime_type' => 'application/json',
    ],
], JSON_UNESCAPED_UNICODE);

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($err)       { echo json_encode(['success' => false, 'message' => 'cURL: ' . $err]); exit; }
if ($code !== 200) {
    $b = json_decode($raw, true);
    echo json_encode(['success' => false, 'message' => $b['error']['message'] ?? "HTTP {$code}"]); exit;
}

$responseBody = json_decode($raw, true);
$text = trim($responseBody['candidates'][0]['content']['parts'][0]['text'] ?? '');

if (!$text) {
    $reason = $responseBody['candidates'][0]['finishReason'] ?? 'unknown';
    echo json_encode(['success' => false, 'message' => "Empty response (finishReason: {$reason})"]); exit;
}

// Strip markdown fences if any
$text = preg_replace(['/^```(json)?\s*/m', '/```\s*$/m'], '', $text);
$data = json_decode(trim($text), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'JSON parse error: ' . json_last_error_msg(), 'raw' => substr($text, 0, 500)]); exit;
}

// ── Sanitize & normalize ────────────────────────────────────────────────
$activity = $data['activity'] ?? [];
$variants = $data['variants'] ?? [];

// Ensure arrays
foreach (['pickup_from_city','dropoff_city','tags','description_points','images','inclusions','exclusions'] as $k) {
    if (!is_array($activity[$k] ?? null)) $activity[$k] = [];
}
if (!is_array($activity['unstructured_data'] ?? null)) $activity['unstructured_data'] = [];

// Sanitize variants
$validModes   = ['none','sic','sedan','suv','van','minibus','coach','boat'];
$validBases   = ['per_pax','per_group'];
$validMkTypes = ['percent','fixed'];
foreach ($variants as &$v) {
    if (empty($v['variant_name']))  $v['variant_name']  = 'Standard';
    if (!in_array($v['price_basis']   ?? '', $validBases))   $v['price_basis']   = 'per_pax';
    if (!in_array($v['transport_mode'] ?? '', $validModes))  $v['transport_mode'] = 'none';
    if (!in_array($v['markup_type']   ?? '', $validMkTypes)) $v['markup_type']   = 'percent';
    $v['net_cost']   = (float)($v['net_cost']   ?? 0);
    $v['sell_price'] = (float)($v['sell_price'] ?? 0);
    $v['markup_value'] = (float)($v['markup_value'] ?? 0);
    foreach (['meal_breakfast','meal_lunch','meal_dinner','ticket_included','guide_included'] as $b) {
        $v[$b] = (bool)($v[$b] ?? false);
    }
    if (!is_array($v['unstructured_data'] ?? null)) $v['unstructured_data'] = [];
}
unset($v);

echo json_encode([
    'success'  => true,
    'activity' => $activity,
    'variants' => array_values($variants),
], JSON_UNESCAPED_UNICODE);