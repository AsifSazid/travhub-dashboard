<?php
/**
 * FILE PATH: /api/ai/social-media.php
 * POST {
 *   content   : string  — raw user input
 *   platform  : string  — facebook|instagram|linkedin|twitter|tiktok
 *   tone      : string  — professional|casual|funny|inspirational|informative
 *   language  : string  — english|bangla|banglish
 *   temperature: float  — 0.1 to 1.0
 *   action    : string  — generate|image_prompt
 * }
 */
ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean(); http_response_code(200); exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../server/ai-gemini.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Test Gemini connectivity
    $apiKey = _geminiApiKey();
    $curlTest = [];
    if ($apiKey) {
        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['contents'=>[['role'=>'user','parts'=>[['text'=>'say hi']]]],'generationConfig'=>['maxOutputTokens'=>5]]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $raw  = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err  = curl_error($ch);
        $errno= curl_errno($ch);
        curl_close($ch);
        $curlTest = [
            'http_code'    => $info['http_code'],
            'curl_error'   => $err,
            'curl_errno'   => $errno,
            'connect_time' => $info['connect_time'],
            'total_time'   => $info['total_time'],
            'raw_snippet'  => substr($raw ?? '', 0, 200),
        ];
    }
    ob_clean();
    echo json_encode([
        'status'           => 'ok',
        'message'          => 'social-media.php API is reachable',
        'gemini_key_exists'=> (bool)$apiKey,
        'gemini_test'      => $curlTest,
        'php_version'      => PHP_VERSION,
        'curl_version'     => curl_version()['version'],
    ], JSON_PRETTY_PRINT);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['content'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'content is required']);
    exit;
}

$content     = trim($data['content']);
$platform    = $data['platform']    ?? 'facebook';
$tone        = $data['tone']        ?? 'professional';
$language    = $data['language']    ?? 'english';
$temperature = (float)($data['temperature'] ?? 0.7);
$action      = $data['action']      ?? 'generate';
$wordLimit   = (int)($data['word_limit'] ?? 150);
$ratio       = $data['ratio']       ?? '1:1';
$temperature = max(0.1, min(1.0, $temperature));
$wordLimit   = max(20, min(1000, $wordLimit));

// Platform-specific constraints
$platformRules = [
    'facebook'  => ['name' => 'Facebook',   'char_limit' => 63206, 'hashtag_limit' => 10, 'notes' => 'Supports long-form. Use line breaks for readability. Emojis encouraged.'],
    'instagram' => ['name' => 'Instagram',  'char_limit' => 2200,  'hashtag_limit' => 30, 'notes' => 'Visual-first. Strong hook. Max 30 hashtags for reach. Add line breaks.'],
    'linkedin'  => ['name' => 'LinkedIn',   'char_limit' => 3000,  'hashtag_limit' => 5,  'notes' => 'Professional network. Structured with insights. 3-5 hashtags max.'],
    'twitter'   => ['name' => 'X (Twitter)','char_limit' => 280,   'hashtag_limit' => 3,  'notes' => 'Concise and punchy. Max 2-3 hashtags. No fluff.'],
    'tiktok'    => ['name' => 'TikTok',     'char_limit' => 2200,  'hashtag_limit' => 15, 'notes' => 'Trend-aware. Short punchy caption. 5-10 hashtags.'],
];

$pInfo = $platformRules[$platform] ?? $platformRules['facebook'];

$languageInstructions = [
    'english'   => 'Write entirely in English.',
    'bangla'    => 'Write entirely in Bangla (Bengali script, বাংলা). Use proper Bengali grammar.',
    'banglish'  => 'Write in Banglish — বাংলা ভাষায় লেখো কিন্তু যেখানে বাংলা শব্দ technical বা awkward সেখানে English word naturally mix করো। Example: "আমাদের Dubai package এ রয়েছে flight, hotel এবং visa সব কিছু included। এখনই book করুন!"',
];

$langRule = $languageInstructions[$language] ?? $languageInstructions['english'];

// ── ACTION: Generate Image Prompt ──────────────────────────
if ($action === 'image_prompt') {
    $ratioDesc = [
        '1:1'  => 'square format (1:1 ratio)',
        '4:5'  => 'portrait format (4:5 ratio)',
        '9:16' => 'vertical stories format (9:16 ratio)',
        '16:9' => 'landscape widescreen format (16:9 ratio)',
        '3:2'  => 'standard photo format (3:2 ratio)',
    ][$ratio] ?? 'square format (1:1 ratio)';

    $system = <<<SYSPROMPT
You are a professional travel poster designer and AI image prompt engineer.

Your job: Create a detailed AI image generation prompt for a travel agency social media post.

STRICT RULES — follow these exactly:
1. NO human faces, NO people, NO hands, NO body parts anywhere in the image
2. The image must be POSTER-STYLE design — cinematic scene + graphic design aesthetic
3. TYPOGRAPHY is required as part of the visual design:
   - Extract the key title/destination/offer from the content (e.g. "Dubai", "50% Off", "Eid Special")
   - Include it as bold, elegant text rendered INTO the scene (like a real travel poster)
   - Typography should feel premium and intentional — not just a caption stuck on top
4. Composition must suit {$ratioDesc}
5. Colors: vibrant, warm, aspirational — travel magazine quality
6. NO watermarks, NO logos, NO QR codes, NO borders

STRUCTURE your prompt exactly like this:
[Cinematic photorealistic background scene — NOT illustrated, NOT cartoon, NOT animated], [poster typography: show the text "[KEY TITLE]" in [font style] at [position]], [mood/lighting], [color palette], [{$ratioDesc}], [quality: ultra-detailed, 8K, RAW photo, photorealistic, professional travel photography + graphic design, shot on Sony A7R, golden hour lighting]

STYLE KEYWORDS to always include at the end:
"photorealistic, hyperrealistic, RAW photograph, DSLR quality, NOT cartoon, NOT illustration, NOT 3D render, NOT painting"

Return ONLY the image generation prompt — no explanation, no JSON, nothing else.
SYSPROMPT;

    $user = "Social media post content:\n\"\"\"\n{$content}\n\"\"\"\n\nPlatform: {$pInfo['name']}\nAspect ratio: {$ratio}\n\nExtract the single most important title/keyword from this content, then write the poster image prompt with that typography included.";

    $result = geminiCall($system, $user, 600, 0.85);
    ob_clean();
    echo json_encode($result['success']
        ? ['status' => 'success', 'image_prompt' => trim($result['text'])]
        : ['status' => 'error',   'message'      => $result['error'] ?? 'Failed']
    );
    exit;
}

// ── ACTION: Generate Image (direct — saves to disk, returns URL) ──────────
if ($action === 'generate_image') {
    $imagePrompt = trim($content);
    $ratio       = $data['ratio'] ?? '1:1';

    $apiKey = _geminiApiKey();
    if (!$apiKey) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Gemini API key not configured']);
        exit;
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-image:generateContent?key=' . $apiKey;

    $ch = curl_init($url);
    // Reinforce no-human rule in the actual generation prompt
    $finalPrompt = $imagePrompt
        . "\n\nCRITICAL: Photorealistic style only. RAW photograph quality. NO cartoon, NO illustration, NO 3D render, NO animation, NO painting. No human faces, no people, no body parts. Poster-style design with typography.";
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'contents'         => [['parts' => [['text' => $finalPrompt]]]],
            'generationConfig' => ['responseModalities' => ['TEXT', 'IMAGE']],
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err || $code !== 200) {
        $errBody = json_decode($raw, true);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $errBody['error']['message'] ?? ($err ?: 'HTTP ' . $code)]);
        exit;
    }

    $data_resp = json_decode($raw, true);
    $base64    = '';
    foreach ($data_resp['candidates'][0]['content']['parts'] ?? [] as $part) {
        if (isset($part['inlineData']['data'])) {
            $base64 = $part['inlineData']['data'];
            break;
        }
    }

    if (!$base64) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'No image generated. Prompt may have triggered safety filters.']);
        exit;
    }

    // ── Save to disk (avoid sending large base64 back to client) ──
    $uploadDir = dirname(__DIR__, 2) . '/uploads/sm-images/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

    $filename  = 'sm-' . time() . '-' . substr(md5($imagePrompt), 0, 8) . '.jpg';
    $filePath  = $uploadDir . $filename;
    $saved     = @file_put_contents($filePath, base64_decode($base64));

    if ($saved !== false) {
        // Return URL — no heavy base64 in response
        ob_clean();
        echo json_encode([
            'status'   => 'success',
            'url'      => '/uploads/sm-images/' . $filename,
            'img'      => null,   // not sending base64 back
        ]);
    } else {
        // Fallback: return base64 if file save failed (client handles it)
        ob_clean();
        echo json_encode([
            'status' => 'success',
            'url'    => null,
            'img'    => 'data:image/jpeg;base64,' . $base64,
        ]);
    }
    exit;
}

// ── ACTION: Generate Content ───────────────────────────────
$ob = '{'; $cb = '}'; // for use inside heredoc
$jsonExample = $ob . '"post":"polished post text","hashtags":["tag1","tag2"],"keywords":["kw1","kw2"],"hook":"strong opening hook","cta":"call-to-action line","char_count":0,"tips":["tip 1","tip 2"]' . $cb;

$system = <<<PROMPT
You are an expert social media content writer for TravHub Global Limited, a Bangladeshi travel agency.

PLATFORM: {$pInfo['name']}
CHAR LIMIT: {$pInfo['char_limit']} characters
HASHTAG LIMIT: max {$pInfo['hashtag_limit']}
PLATFORM NOTES: {$pInfo['notes']}
TONE: {$tone}
TARGET LENGTH: approximately {$wordLimit} words
LANGUAGE: {$langRule}

TONE GUIDANCE:
- professional → authoritative, trust-building, polished
- casual → friendly, conversational, like talking to a friend
- funny → witty and playful, humor feels natural not forced
- inspirational → emotional, dream-invoking, motivating
- informative → clear facts, structured, educational

WRITING GUIDELINES:
- Use emojis naturally where they enhance readability
- For travel: paint a picture, evoke wanderlust or urgency
- Add line breaks between paragraphs
- End with a strong call to action

OUTPUT: Return ONLY a single raw JSON object. No markdown. No explanation. No code fences.
Start your response with { and end with }

FORMAT: {$jsonExample}

FIELD RULES:
- post: complete ready-to-publish text
- hashtags: no # symbol, lowercase, underscores for spaces, max {$pInfo['hashtag_limit']}
- keywords: SEO terms, lowercase, 5-8 items
- hook: 1-sentence strong alternative opening
- cta: short actionable closing line
- char_count: set 0 (recalculated server-side)
- tips: exactly 2 short actionable {$pInfo['name']}-specific tips
PROMPT;

$result = geminiCall($system, $content, 4000, $temperature);

if (!$result['success']) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $result['error'] ?? 'Generation failed']);
    exit;
}

// ── Parse JSON — aggressive cleanup ──────────────────────────
$text = $result['text'];

// Strip markdown code fences (```json ... ``` or ``` ... ```)
$text = preg_replace('/^```(?:json)?\s*/m', '', $text);
$text = preg_replace('/```\s*$/m',          '', $text);
$text = trim($text);

// If text starts mid-JSON (truncated response), try to fix
if (!str_starts_with($text, '{')) {
    $start = strpos($text, '{');
    if ($start !== false) $text = substr($text, $start);
}

// Try to close truncated JSON
$parsed = json_decode($text, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Attempt recovery: extract what we can
    $post      = '';
    $hashtags  = [];
    $keywords  = [];
    $hook      = '';
    $cta       = '';
    $tips      = [];

    // Extract "post" value
    if (preg_match('/"post"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/su', $text, $m))
        $post = stripslashes($m[1]);

    // Extract arrays
    if (preg_match('/"hashtags"\s*:\s*\[([^\]]*)\]/s', $text, $m))
        $hashtags = array_values(array_filter(array_map(fn($t) => trim(trim($t), '"\''), explode(',', $m[1]))));
    if (preg_match('/"keywords"\s*:\s*\[([^\]]*)\]/s', $text, $m))
        $keywords = array_values(array_filter(array_map(fn($t) => trim(trim($t), '"\''), explode(',', $m[1]))));
    if (preg_match('/"tips"\s*:\s*\[([^\]]*)\]/s', $text, $m))
        $tips = array_values(array_filter(array_map(fn($t) => trim(trim($t), '"\''), explode(',', $m[1]))));
    if (preg_match('/"hook"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/su', $text, $m))
        $hook = stripslashes($m[1]);
    if (preg_match('/"cta"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/su', $text, $m))
        $cta = stripslashes($m[1]);

    // If we couldn't extract post either, do a second Gemini call asking for plain post only
    if (!$post) {
        $retry = geminiCall(
            "You are a social media writer. Return ONLY the post text, no JSON, no explanation.",
            "Write a {$tone} {$pInfo['name']} post in this language: {$langRule}\n\nContent:\n{$content}",
            2000,
            $temperature
        );
        $post = $retry['success'] ? trim($retry['text']) : $content;
    }

    ob_clean();
    echo json_encode([
        'status'     => 'success',
        'post'       => $post,
        'hashtags'   => $hashtags,
        'keywords'   => $keywords,
        'hook'       => $hook,
        'cta'        => $cta,
        'char_count' => mb_strlen($post),
        'tips'       => $tips,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Success path ─────────────────────────────────────────────
$parsed['char_count'] = mb_strlen($parsed['post'] ?? '');

// Ensure arrays exist
foreach (['hashtags','keywords','tips'] as $k)
    if (!is_array($parsed[$k] ?? null)) $parsed[$k] = [];

ob_clean();
echo json_encode(['status' => 'success'] + $parsed, JSON_UNESCAPED_UNICODE);