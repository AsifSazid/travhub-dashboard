<?php
// FILE PATH: /api/running-works/ai-chat.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$GEMINI_API_KEY = trim(file_get_contents('../../gemini-apikey.txt'));
$GEMINI_MODEL   = 'gemini-2.5-flash';

$input   = json_decode(file_get_contents('php://input'), true);
$message = $input['message']    ?? '';
$history = $input['history']    ?? [];
$context = $input['context']    ?? [];
$system  = $input['system']     ?? '';

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'No message provided.']);
    exit;
}

// Build contents array for Gemini (history + current message)
$contents = [];

// Add history
foreach ($history as $h) {
    $role = $h['role'] === 'assistant' ? 'model' : 'user';
    $contents[] = [
        'role'  => $role,
        'parts' => [['text' => $h['content']]],
    ];
}

// Add current message
$contents[] = [
    'role'  => 'user',
    'parts' => [['text' => $message]],
];

$requestData = [
    'system_instruction' => [
        'parts' => [['text' => $system ?: 'You are a helpful assistant for TravHub, a travel agency management system.']],
    ],
    'contents' => $contents,
    'generationConfig' => [
        'temperature'     => 0.7,
        'maxOutputTokens' => 1024,
    ],
];

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key={$GEMINI_API_KEY}";

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($requestData),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
]);

$result   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data  = json_decode($result, true);
    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated.';
    echo json_encode(['success' => true, 'reply' => $reply]);
} else {
    $err = json_decode($result, true);
    echo json_encode([
        'success' => false,
        'message' => $err['error']['message'] ?? 'Gemini API error.',
        'code'    => $httpCode,
    ]);
}