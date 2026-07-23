<?php
// TEMP DEBUG FILE - delete after use
require_once '../../server/ai-gemini.php';
header('Content-Type: application/json');

// Test with a real PDF from SMB or check PDF size limit
// First, let's check what happens with pdf mime_type directly

$apiKey = _geminiApiKey();
$model  = 'gemini-2.5-flash';
$url    = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// Small test PDF (minimal valid PDF)
$pdfContent = "%PDF-1.0\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 3 3]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF";
$tmpPdf = sys_get_temp_dir() . '/test_' . uniqid() . '.pdf';
file_put_contents($tmpPdf, $pdfContent);

$payload = json_encode([
    'contents' => [[
        'role' => 'user',
        'parts' => [
            ['inline_data' => ['mime_type' => 'application/pdf', 'data' => base64_encode(file_get_contents($tmpPdf))]],
            ['text' => 'What is in this PDF? Reply in JSON: {"content": "..."}'],
        ]
    ]],
    'generationConfig' => ['maxOutputTokens' => 200, 'temperature' => 0.2],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30, CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
unlink($tmpPdf);

echo json_encode([
    'http_code' => $code,
    'response'  => json_decode($raw, true),
]);