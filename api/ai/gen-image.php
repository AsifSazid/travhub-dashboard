<?php
/**
 * api/ai/gen-image.php
 * Proxy for Google Gemini Native Image Generation (avoids browser CORS)
 * POST { prompt, key }
 */
session_start();

// Display errors for debugging (Turn off in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if (file_exists('../../server/api_bootstrap.php')) {
    require_once '../../server/api_bootstrap.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']); 
    exit;
}

// Read raw JSON input
$in     = json_decode(file_get_contents('php://input'), true) ?: [];
$prompt = trim($in['prompt'] ?? '');
$key    = trim($in['key']    ?? '');

if (!$key) {
    $paths = [
        dirname(__DIR__, 2) . '/banana-key.txt',
        __DIR__ . '/banana-key.txt'
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) { 
            $key = preg_replace('/\s+/', '', file_get_contents($p)); 
            break; 
        }
    }
}

if (!$key) { 
    echo json_encode(['success' => false, 'message' => 'API key not found. Add your Google key to banana-key.txt']); 
    exit; 
}

if (!$prompt) { 
    echo json_encode(['success' => false, 'message' => 'Prompt required']); 
    exit; 
}

// FIX: Target the designated native image generation model architecture
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-image:generateContent?key=' . $key;

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS => json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'responseModalities' => ['TEXT', 'IMAGE']
        ]
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 60,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$resp = curl_exec($ch);
$err  = curl_error($ch);
$info = curl_getinfo($ch); 
curl_close($ch);

if ($err) {
    echo json_encode([
        'success'       => false,
        'message'       => 'cURL connection error.',
        'error_details' => $err,
        'http_code'     => $info['http_code']
    ]); 
    exit;
}

if (empty($resp)) {
    echo json_encode([
        'success'   => false,
        'message'   => 'Received empty response body from Google.',
        'http_code' => $info['http_code']
    ]);
    exit;
}

$data = json_decode($resp, true);

if (isset($data['error'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Google API Error: ' . ($data['error']['message'] ?? 'Unknown issue'),
        'raw'     => $data
    ]); 
    exit;
}

// Dig the inline dynamic data content structure from the candidate generation
$base64Image = '';
if (isset($data['candidates'][0]['content']['parts'])) {
    foreach ($data['candidates'][0]['content']['parts'] as $part) {
        if (isset($part['inlineData']['data'])) {
            $base64Image = $part['inlineData']['data'];
            break;
        }
    }
}

if (!$base64Image) {
    echo json_encode([
        'success' => false,
        'message' => 'No inline image data generated. The prompt might have triggered safety filters.',
        'raw'     => $data
    ]); 
    exit;
}

$imgUrl = 'data:image/jpeg;base64,' . $base64Image;
$image = 'data:image/jpeg;base64,' . $base64Image;

// Save to file instead of returning raw base64
$uploadDir  = dirname(__DIR__, 2) . '/uploads/ai-images/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

$filename   = 'ai-' . uniqid() . '.jpg';
$filePath   = $uploadDir . $filename;
$fileBytes  = base64_decode($base64Image);

if (!$fileBytes || file_put_contents($filePath, $fileBytes) === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to save image file']); exit;
}

// Return a URL the browser can load
// Adjust this path to match your project structure
$imgUrl = '/uploads/ai-images/' . $filename;

echo json_encode([
    'success' => true,
    'url'     => $imgUrl,
    'img'     => $image
], JSON_UNESCAPED_UNICODE);