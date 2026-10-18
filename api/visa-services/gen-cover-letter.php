<?php
/**
 * FILE PATH: /api/visa-services/gen-cover-letter.php
 * Generate AI cover letter for a visa traveler via Gemini
 *
 * POST body (JSON):
 *   traveler_name, passport_no, profession_type,
 *   country, visa_type, visa_category, duration,
 *   work_sys_id, client_name
 *   (also accepts: visa_title, country_name, visa_type_name, duration_label — legacy)
 */

ob_start();
include_once('../../authenticate.php');
ob_end_clean();

require_once '../../server/db_connection.php';
header('Content-Type: application/json; charset=utf-8');

// ── Gemini API key — use same source as classify-document.php ──
$GEMINI_API_KEY = '';
foreach ([
    __DIR__ . '/../../gemini-apikey.txt',
    __DIR__ . '/../../../gemini-apikey.txt',
] as $p) {
    if (file_exists($p)) { $GEMINI_API_KEY = trim(file_get_contents($p)); break; }
}

// Fallback: try gemini-config.php
if (empty($GEMINI_API_KEY)) {
    $cfgFile = __DIR__ . '/../../server/gemini-config.php';
    if (file_exists($cfgFile)) {
        include_once($cfgFile);
        $GEMINI_API_KEY = $GEMINI_API_KEY ?? '';
    }
}

if (empty($GEMINI_API_KEY)) {
    echo json_encode(['status' => 'error', 'message' => 'AI not configured — Gemini API key not found']);
    exit;
}

$GEMINI_MODEL = trim(@file_get_contents(__DIR__ . '/../../gemini-model.txt')) ?: 'gemini-2.5-flash';

// ── Input ───────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$travelerName   = trim($body['traveler_name']   ?? '');
$passportNo     = trim($body['passport_no']     ?? '');
$profType       = trim($body['profession_type'] ?? 'general');
$countryName    = trim($body['country']         ?? $body['country_name']   ?? '');
$visaTypeName   = trim($body['visa_type']       ?? $body['visa_type_name'] ?? '');
$visaCategory   = trim($body['visa_category']   ?? $body['visa_category_name'] ?? '');
$durationLabel  = trim($body['duration']        ?? $body['duration_label'] ?? '');
$workSysId      = trim($body['work_sys_id']     ?? '');
$clientName     = trim($body['client_name']     ?? '');

// ── Get agency name from DB settings ────────────────────────
$agencyName = 'TravHub Global Limited';
try {
    $s = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'agency_name' LIMIT 1");
    if ($row = $s->fetch(PDO::FETCH_COLUMN)) $agencyName = $row;
} catch (Throwable $e) {}

$today = date('d F Y');

// ── Occupation label ─────────────────────────────────────────
$occupationMap = [
    'general'        => 'General',
    'student'        => 'Student',
    'employment'     => 'Employed',
    'businessman'    => 'Business Person / Entrepreneur',
    'non_employment' => 'Self-employed / Non-employed',
];
// Use actual job title from app_info if provided
$appInfo    = $body['app_info'] ?? [];
$occupation = !empty($appInfo['occupation'])
    ? trim($appInfo['occupation'])
    : ($occupationMap[$profType] ?? ucfirst($profType));
$employer   = trim($appInfo['employer_name'] ?? '');

// ── Prompt ───────────────────────────────────────────────────
$templateSection = $clTemplate
    ? "REFERENCE TEMPLATE (use this style and structure — adapt for this specific applicant):

{$clTemplate}

---

Now write a new cover letter for this applicant following the above template's style:"
    : "Write a complete formal cover letter with 4 paragraphs:";

$prompt = <<<PROMPT
{$templateSection}

Write a complete formal visa application cover letter. Output the FULL letter body — all paragraphs — not just the header.

Applicant Details:
- Full Name: {$travelerName}
- Passport Number: {$passportNo}
- Occupation/Profession: {$occupation}
- Employer/Organization: {$employer}
- Destination Country: {$countryName}
- Visa Type: {$visaTypeName}
- Visa Category: {$visaCategory}
- Duration of Stay: {$durationLabel}
- Application Date: {$today}
- Travel Agency: {$agencyName}

Letter structure (write ALL of these):

{$today}

To the Visa Officer,
{$countryName} Embassy/Consulate

Subject: Application for {$visaTypeName} Visa — {$travelerName} (Passport: {$passportNo})

[Paragraph 1 — Introduction and purpose of visit: who is applying, what visa, why visiting {$countryName}, dates of intended travel]

[Paragraph 2 — Financial capacity: applicant has sufficient funds, accommodation arranged, all expenses will be covered]

[Paragraph 3 — Ties to home country and intent to return: employment/family/property ties, commitment to return before visa expiry, will abide by all visa conditions]

[Paragraph 4 — Closing: thank the visa officer, mention supporting documents are attached, request favourable consideration]

Yours sincerely,

{$travelerName}
Passport No: {$passportNo}

Submitted by: {$agencyName}

Rules:
- Write COMPLETE paragraphs with real content — do NOT use placeholder text or brackets
- Each paragraph should be 3-5 sentences
- Plain text only — no markdown, no asterisks, no bullet points
- Use line breaks between sections
PROMPT;

// ── Gemini call ───────────────────────────────────────────────
$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature'     => 0.7,
        'maxOutputTokens' => 2000,
    ],
], JSON_UNESCAPED_UNICODE);

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key={$GEMINI_API_KEY}";

$maxAttempts = 2;
$draft = null;
$lastErr = '';

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) { $lastErr = $err; continue; }
    if ($code !== 200) {
        $b = json_decode($raw, true);
        $lastErr = $b['error']['message'] ?? "HTTP {$code}";
        break; // 4xx — don't retry
    }

    $resp  = json_decode($raw, true);
    $draft = trim($resp['candidates'][0]['content']['parts'][0]['text'] ?? '');
    if ($draft) break;
    $lastErr = 'Empty response from AI';
}

if (!$draft) {
    echo json_encode(['status' => 'error', 'message' => 'Cover letter generation failed: ' . $lastErr]);
    exit;
}

echo json_encode(['status' => 'success', 'draft' => $draft]);