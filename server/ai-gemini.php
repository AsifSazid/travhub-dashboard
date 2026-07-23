<?php
/**
 * server/ai-gemini.php — Gemini API wrapper
 * Uses Google Gemini 2.0 Flash via generateContent endpoint
 *
 * Functions:
 *   geminiCall(system, user, maxTokens, temperature) → ['success', 'text']
 *   geminiJSON(system, user, maxTokens)              → ['success', 'data']
 */

function _geminiApiKey(): string {
    static $key = null;
    if ($key !== null) return $key;
    foreach ([
        // '/../gemini-apikey.txt',
        // '/../../gemini-apikey.txt',
        __DIR__ . '/../gemini-apikey.txt',
        __DIR__ . '/../../gemini-apikey.txt',
    ] as $p) {
        if (file_exists($p)) { $key = trim(file_get_contents($p)); return $key; }
    }
    return '';
}

/**
 * geminiCallWithFile — image বা PDF file পাঠিয়ে Gemini থেকে data extract করো
 * @param string $filePath  local file path (temp)
 * @param string $mimeType  image/jpeg, image/png, application/pdf etc
 * @param string $prompt    extraction prompt
 * @return array|null       parsed JSON array/object or null on failure
 */
function geminiCallWithFile(string $filePath, string $mimeType, string $prompt, int $maxTokens = 8192): ?array {
    $apiKey = _geminiApiKey();
    if (!$apiKey || !file_exists($filePath)) return null;

    $model = 'gemini-2.5-flash';
    $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $fileData = base64_encode(file_get_contents($filePath));
    $payload  = json_encode([
        'contents' => [[
            'role'  => 'user',
            'parts' => [
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $fileData]],
                ['text' => $prompt],
            ]
        ]],
        'generationConfig' => [
            'maxOutputTokens'  => $maxTokens,               // Dynamically uses passed $maxTokens (8192)
            'temperature'      => 0.1,
            'responseMimeType' => 'application/json',        // Guarantees pure JSON without markdown backticks
            'thinkingConfig'   => [
                'thinkingBudget' => 1024                     // Minimal headroom for structural parsing
            ]
        ],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$raw) return null;

    $body = json_decode($raw, true);
    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (!$text) return null;

    // Decode pure JSON directly (no regex needed when responseMimeType is application/json)
    $decoded = json_decode(trim($text), true);
    return is_array($decoded) ? $decoded : null;
}

function geminiCall(string $system, string $user, int $maxTokens = 1000, float $temperature = 0.5): array {
    $apiKey = _geminiApiKey();
    if (!$apiKey) return ['success' => false, 'error' => 'Gemini API key not configured'];

    $model = 'gemini-2.5-flash';
    // $model = 'gemini-3.1-flash-lite';
    // $model = 'gemini-3.5-flash';
    $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $payload = json_encode([
        'system_instruction' => [
            'parts' => [['text' => $system]]
        ],
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $user]]]
        ],
        'generationConfig' => [
            'maxOutputTokens' => $maxTokens,
            'temperature'     => $temperature,
        ],
    ], JSON_UNESCAPED_UNICODE);

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

    if ($err)      return ['success' => false, 'error' => 'cURL: ' . $err];
    if ($code !== 200) {
        $b = json_decode($raw, true);
        return ['success' => false, 'error' => $b['error']['message'] ?? "HTTP {$code}", 'raw' => $raw];
    }

    $body = json_decode($raw, true);
    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

    if (trim($text) === '') {
        $reason = $body['candidates'][0]['finishReason'] ?? 'unknown';
        return ['success' => false, 'error' => "Empty response (finishReason: {$reason})"];
    }

    return ['success' => true, 'text' => trim($text)];
}

function geminiJSON(string $system, string $user, int $maxTokens = 1200): array {
    $r = geminiCall($system, $user, $maxTokens, 0.2);
    if (!$r['success']) return $r;

    $text = trim(preg_replace(['/^```(json)?\s*/m', '/```\s*$/m'], '', $r['text']));

    $data = json_decode($text, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error'   => 'JSON parse error: ' . json_last_error_msg(),
            'raw'     => $text,
        ];
    }

    return ['success' => true, 'data' => $data];
}