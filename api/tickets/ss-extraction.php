<?php
header('Content-Type: application/json');

// Configuration
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');
define('REQUEST_TIMEOUT', 60);

// MIME type detection function (unchanged)
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

// Image extraction prompt
function getImagePrompt() {
    return '
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

Transit / connection rules:
- A segment represents the journey from its starting airport to its final destination.
- The top-level dep_airport/arr_airport for a segment is the FIRST flight of that journey only.
- If the journey has a layover/transit (e.g. DAC -> SIN [transit] -> MNL), set has_transit = true, fill transit_time (e.g. "Tr 3 Hours"), and put EACH onward flight in the connections array.
- If the journey is a single non-stop flight, set has_transit = false and leave connections as [].
- Each connections entry needs dep_airport, dep_time, arr_airport, arr_time, and arr_day_indicator. flight_no is optional.

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
      "has_transit": false,
      "transit_time": "",
      "connections": [
        {
          "dep_airport": "",
          "dep_time": "HH:MM",
          "arr_airport": "",
          "arr_time": "HH:MM",
          "arr_day_indicator": false,
          "flight_no": ""
        }
      ]
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
}

// Text extraction prompt
function getTextPrompt() {
    return 'Parse this air ticket quotation text and extract structured data.
Return ONLY valid JSON. No markdown. No explanation.

Rules:
- trip_option must be exactly one of: "One Way", "Round Trip", "Multi City"
- class must be exactly one of: "Economy", "Business"
- Dates in YYYY-MM-DD format. Times in 24-hour HH:MM format.
- For One Way: one segment. For Round Trip: outbound then inbound. For Multi City: in route order.
- arr_day_indicator is true if arrival is the next day (+1).
- Prices as numbers only (no commas, no currency symbols).
- If a field is not visible or not provided, use empty string or 0.

Transit / connection rules:
- A segment represents the journey from its starting airport to its final destination.
- The top-level dep_airport/arr_airport for a segment is the FIRST flight of that journey only.
- If the journey has a layover/transit (e.g. DAC -> SIN [transit] -> MNL), set has_transit = true, fill transit_time (e.g. "Tr 3 Hours"), and put EACH onward flight in the connections array.
- If the journey is a single non-stop flight, set has_transit = false and leave connections as [].
- Each connections entry needs dep_airport, dep_time, arr_airport, arr_time, and arr_day_indicator. flight_no is optional.

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
      "has_transit": false,
      "transit_time": "",
      "connections": []
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

Text to parse:';
}

// Function to call Gemini API for image extraction
function callGeminiImage($apiKey, $imageBase64, $mimeType) {
    $prompt = getImagePrompt();
    
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt],
                    [
                        "inline_data" => [
                            "mime_type" => $mimeType,
                            "data" => $imageBase64
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    return callGeminiAPI($apiKey, $payload);
}

// Function to call Gemini API for text extraction
function callGeminiText($apiKey, $textInput) {
    $prompt = getTextPrompt() . "\n\n" . $textInput;
    
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];
    
    return callGeminiAPI($apiKey, $payload);
}

// Generic Gemini API caller
function callGeminiAPI($apiKey, $payload) {
    $url = GEMINI_API_URL;
    
    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => REQUEST_TIMEOUT,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "x-goog-api-key: {$apiKey}"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => $error
        ];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'Gemini API request failed',
            'http_code' => $httpCode,
            'response' => $result
        ];
    }
    
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $text = trim($text);
    $text = str_replace(['```json', '```'], '', $text);
    $text = trim($text);
    
    $extractedData = json_decode($text, true);
    
    if (!$extractedData) {
        return [
            'success' => false,
            'error' => 'Could not parse Gemini response',
            'raw' => $text
        ];
    }
    
    return [
        'success' => true,
        'data' => $extractedData,
        'raw_response' => $text
    ];
}

// Function to validate and clean extracted data
function validateExtractedData($data) {
    $requiredFields = ['trip_option', 'class', 'segments'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            return false;
        }
    }
    
    // Validate trip_option
    $validTripOptions = ['One Way', 'Round Trip', 'Multi City'];
    if (!in_array($data['trip_option'], $validTripOptions)) {
        $data['trip_option'] = 'One Way'; // Default fallback
    }
    
    // Validate class
    $validClasses = ['Economy', 'Business'];
    if (!in_array($data['class'], $validClasses)) {
        $data['class'] = 'Economy'; // Default fallback
    }
    
    // Ensure numeric values are numbers
    $numericFields = ['pax_adult', 'pax_child', 'pax_infant', 'price_1_adult', 'price_1_child', 'price_1_infant', 'price_2_adult', 'price_2_child', 'price_2_infant'];
    foreach ($numericFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = is_numeric($data[$field]) ? (float)$data[$field] : 0;
        } else {
            $data[$field] = 0;
        }
    }
    
    return $data;
}

// Main execution starts here

// Load Gemini API key
$apiKeyFile = '../../gemini-apikey.txt';

if (!file_exists($apiKeyFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Gemini API key file not found',
        'error_code' => 'MISSING_API_KEY'
    ]);
    exit;
}

$geminiApiKey = trim(file_get_contents($apiKeyFile));

if (empty($geminiApiKey)) {
    echo json_encode([
        'success' => false,
        'message' => 'Gemini API key is empty',
        'error_code' => 'EMPTY_API_KEY'
    ]);
    exit;
}

// Get mode from request
$mode = isset($_POST['mode']) ? strtolower(trim($_POST['mode'])) : 'image';

// Validate mode
if (!in_array($mode, ['image', 'text'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid mode. Must be "image" or "text"',
        'error_code' => 'INVALID_MODE'
    ]);
    exit;
}

// Process based on mode
if ($mode === 'image') {
    // Image mode - existing logic
    if (!isset($_FILES['screenshot'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Screenshot file is required when mode=image',
            'error_code' => 'MISSING_FILE'
        ]);
        exit;
    }
    
    if ($_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $errorMsg = $errorMessages[$_FILES['screenshot']['error']] ?? 'Unknown upload error';
        
        echo json_encode([
            'success' => false,
            'message' => 'File upload failed: ' . $errorMsg,
            'error_code' => $_FILES['screenshot']['error']
        ]);
        exit;
    }
    
    $imageTmp = $_FILES['screenshot']['tmp_name'];
    $originalName = $_FILES['screenshot']['name'] ?? '';
    
    // Check file size (limit to 10MB)
    $fileSize = $_FILES['screenshot']['size'];
    if ($fileSize > 10 * 1024 * 1024) {
        echo json_encode([
            'success' => false,
            'message' => 'File size exceeds 10MB limit',
            'error_code' => 'FILE_TOO_LARGE'
        ]);
        exit;
    }
    
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
            'detected_mime' => $imageMime,
            'allowed_types' => $allowedMimeTypes
        ]);
        exit;
    }
    
    $imageBase64 = base64_encode(file_get_contents($imageTmp));
    $result = callGeminiImage($geminiApiKey, $imageBase64, $imageMime);
    
    if (!$result['success']) {
        echo json_encode([
            'success' => false,
            'message' => $result['error'],
            'mode' => 'image',
            'http_code' => $result['http_code'] ?? null,
            'raw_response' => $result['raw'] ?? null
        ]);
        exit;
    }
    
    $validatedData = validateExtractedData($result['data']);
    
    echo json_encode([
        'success' => true,
        'mode' => 'image',
        'mime_type' => $imageMime,
        'data' => $validatedData,
        'raw_response' => $result['raw_response'] ?? null
    ]);
    
} elseif ($mode === 'text') {
    // Text mode - new functionality
    $textInput = isset($_POST['text_input']) ? trim($_POST['text_input']) : '';
    
    if (empty($textInput)) {
        echo json_encode([
            'success' => false,
            'message' => 'text_input is required when mode=text',
            'error_code' => 'MISSING_TEXT_INPUT'
        ]);
        exit;
    }
    
    // Limit text input size (prevent abuse)
    if (strlen($textInput) > 50000) {
        echo json_encode([
            'success' => false,
            'message' => 'Text input exceeds 50000 character limit',
            'error_code' => 'TEXT_TOO_LARGE'
        ]);
        exit;
    }
    
    $result = callGeminiText($geminiApiKey, $textInput);
    
    if (!$result['success']) {
        echo json_encode([
            'success' => false,
            'message' => $result['error'],
            'mode' => 'text',
            'http_code' => $result['http_code'] ?? null,
            'raw_response' => $result['raw'] ?? null
        ]);
        exit;
    }
    
    $validatedData = validateExtractedData($result['data']);
    
    echo json_encode([
        'success' => true,
        'mode' => 'text',
        'data' => $validatedData,
        'raw_response' => $result['raw_response'] ?? null
    ]);
}
?>