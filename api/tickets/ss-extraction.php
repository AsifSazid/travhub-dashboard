<?php
header('Content-Type: application/json');

function detectMimeType($filePath, $originalName = '') {
    $handle = fopen($filePath, 'rb');

    if ($handle) {
        $bytes = fread($handle, 12);
        fclose($handle);

        $hex = strtoupper(bin2hex($bytes));

        if (strpos($hex, '89504E47') === 0) return 'image/png';
        if (strpos($hex, 'FFD8FF') === 0) return 'image/jpeg';
        if (strpos($hex, '52494646') === 0 && strpos($hex, '57454250') !== false) return 'image/webp';
        if (strpos($hex, '47494638') === 0) return 'image/gif';
        if (strpos($hex, '424D') === 0) return 'image/bmp';
    }

    $ext = strtolower(pathinfo($originalName ?: $filePath, PATHINFO_EXTENSION));

    $mimeMap = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp'
    ];

    return $mimeMap[$ext] ?? 'image/jpeg';
}

$apiKeyFile = '../../gemini-apikey.txt';

if (!file_exists($apiKeyFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Gemini API key file not found'
    ]);
    exit;
}

$geminiApiKey = trim(file_get_contents($apiKeyFile));

if (empty($geminiApiKey)) {
    echo json_encode([
        'success' => false,
        'message' => 'Gemini API key is empty'
    ]);
    exit;
}

if (!isset($_FILES['screenshot'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Screenshot is required'
    ]);
    exit;
}

if ($_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'File upload failed',
        'error_code' => $_FILES['screenshot']['error']
    ]);
    exit;
}

$imageTmp = $_FILES['screenshot']['tmp_name'];
$originalName = $_FILES['screenshot']['name'] ?? '';

$imageMime = detectMimeType($imageTmp, $originalName);

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
    'image/bmp'
];

if (!in_array($imageMime, $allowedMimeTypes)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unsupported image type',
        'detected_mime' => $imageMime
    ]);
    exit;
}

$imageBase64 = base64_encode(file_get_contents($imageTmp));

$prompt = '
Extract air ticket quotation data from this screenshot.

Return ONLY valid JSON. No markdown. No explanation.

Rules:
- trip_option must be exactly one of: "One Way", "Round Trip", "Multi City"
- class must be exactly one of: "Economy", "Business"
- Dates in YYYY-MM-DD format. Times in 24-hour HH:MM format.
- For One Way: one segment. For Round Trip: outbound then inbound. For Multi City: in route order.
- arr_day_indicator is true if arrival is the next day (+1).
- Prices as numbers only (no commas, no currency symbols).
- If a field is not visible, leave it as empty string or 0.

Use this exact structure:

{
  "trip_option": "One Way",
  "route": "",
  "class": "Economy",
  "pax_adult": 0,
  "pax_child": 0,
  "pax_infant": 0,
  "segments": [
    {
      "segment_title": "",
      "date": "YYYY-MM-DD",
      "airline": "",
      "flight_no": "",
      "dep_airport": "",
      "dep_time": "HH:MM",
      "arr_airport": "",
      "arr_time": "HH:MM",
      "arr_day_indicator": false,
      "transit_time": ""
    }
  ],
  "baggage_1_desc": "Without Baggage",
  "price_1_adult": 0,
  "price_1_child": 0,
  "price_1_infant": 0,
  "baggage_2_desc": "With 30 Kg Check-IN + 7 Kg Cabin Baggage",
  "price_2_adult": 0,
  "price_2_child": 0,
  "price_2_infant": 0,
  "refundable_status": "Refundable",
  "changeable_status": "Changeable",
  "notes": []
}
';

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt],
                [
                    "inline_data" => [
                        "mime_type" => $imageMime,
                        "data" => $imageBase64
                    ]
                ]
            ]
        ]
    ]
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-goog-api-key: {$geminiApiKey}"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($error) {
    echo json_encode([
        'success' => false,
        'message' => $error
    ]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode !== 200) {
    echo json_encode([
        'success' => false,
        'message' => 'Gemini API request failed',
        'http_code' => $httpCode,
        'response' => $result
    ]);
    exit;
}

$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

$text = trim($text);
$text = str_replace(['```json', '```'], '', $text);
$text = trim($text);

$extractedData = json_decode($text, true);

if (!$extractedData) {
    echo json_encode([
        'success' => false,
        'message' => 'Could not parse Gemini response',
        'raw' => $text
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'mime_type' => $imageMime,
    'data' => $extractedData
]);
