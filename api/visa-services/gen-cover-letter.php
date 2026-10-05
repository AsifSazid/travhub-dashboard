<?php
/**
 * FILE PATH: /api/visa-services/gen-cover-letter.php
 * Generate AI cover letter for a visa traveler via Gemini
 *
 * POST body (JSON):
 *   traveler_name, passport_no, profession_type,
 *   visa_title, country_name, visa_type_name, duration_label
 */

ob_start();
include_once('../../authenticate.php');
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

// ── Read Gemini config ──────────────────────────────────────
$cfgFile = __DIR__ . '/../../server/gemini-config.php';
if (!file_exists($cfgFile)) {
    echo json_encode(['status'=>'error','message'=>'AI not configured']);
    exit;
}
include_once($cfgFile);
if (empty($GEMINI_API_KEY)) {
    echo json_encode(['status'=>'error','message'=>'Gemini API key missing']);
    exit;
}

// ── Input ───────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$travelerName  = trim($body['traveler_name']  ?? '');
$passportNo    = trim($body['passport_no']    ?? '');
$profType      = trim($body['profession_type']?? 'general');
$visaTitle     = trim($body['visa_title']     ?? '');
$countryName   = trim($body['country_name']   ?? '');
$visaTypeName  = trim($body['visa_type_name'] ?? '');
$durationLabel = trim($body['duration_label'] ?? '');
$agencyName    = trim($body['agency_name']    ?? 'Our Agency');
$today         = date('d F Y');

// ── Prompt ──────────────────────────────────────────────────
$prompt = <<<PROMPT
Write a formal visa cover letter for the following person applying for a {$visaTitle}.

Applicant Details:
- Full Name: {$travelerName}
- Passport Number: {$passportNo}
- Occupation/Status: {$profType}
- Destination Country: {$countryName}
- Visa Type: {$visaTypeName}
- Duration: {$durationLabel}
- Application Date: {$today}
- Submitted by: {$agencyName}

Requirements:
- Write in a professional, formal tone suitable for a visa application
- Include: purpose of visit, intent to comply with visa conditions, intent to return
- Address: "To the Visa Officer, {$countryName} Embassy/Consulate"
- Keep it concise (3-4 paragraphs)
- End with "Yours sincerely," and leave space for the applicant's signature
- Do NOT include any placeholder text like [Your Address] — omit address blocks entirely
- Output plain text only, no markdown
PROMPT;

// ── Gemini call ─────────────────────────────────────────────
$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 800],
]);

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$GEMINI_API_KEY}";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 30,
]);
$raw = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['status'=>'error','message'=>'AI request failed: '.$err]);
    exit;
}

$resp = json_decode($raw, true);
$text = $resp['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$text) {
    echo json_encode(['status'=>'error','message'=>'AI returned empty response']);
    exit;
}

echo json_encode(['status'=>'success','draft'=>trim($text)]);