<?php
header('Content-Type: application/json');

// Configuration
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');
define('REQUEST_TIMEOUT', 60);

// MIME type detection function
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

// Image extraction prompt for hotels
function getImagePrompt() {
    return '
Extract hotel quotation data from this screenshot.

Return ONLY valid JSON. No markdown. No explanation.

Rules:
- Dates in YYYY-MM-DD format
- room_size can be: "Single", "Double", "Twin", "Triple", "Quad", "Suite", "Studio", "Executive", "Deluxe", "Standard", "Family", "Presidential"
- no_rooms: number of rooms (as integer string)
- adults: number of adults (as integer string)
- child_count: number of children (as integer string)
- child_ages: comma-separated ages (e.g., "3,5,7") or empty string if no children
- room_only: price for room only (as string with no commas, no currency symbols)
- breakfast: price for breakfast (as string with no commas, no currency symbols)
- room_only_type: "Total" or "Per Night" or "Per Person"
- breakfast_type: "Total" or "Per Night" or "Per Person"
- If a field is not visible, use empty string
- notes: array of strings for any special conditions or additional info

Use this exact structure:

{
  "hotel_name": "",
  "address": "",
  "check_in": "YYYY-MM-DD",
  "check_out": "YYYY-MM-DD",
  "rooms": [
    {
      "room_type": "",
      "room_size": "",
      "no_rooms": "",
      "adults": "",
      "child_count": "",
      "child_ages": "",
      "room_only": "",
      "room_only_type": "Total",
      "breakfast": "",
      "breakfast_type": "Total"
    }
  ],
  "notes": []
}
';
}

// Text extraction prompt for hotels
function getTextPrompt() {
    return 'Parse this hotel quotation text and extract structured data.
Return ONLY valid JSON. No markdown. No explanation.

Rules:
- Dates in YYYY-MM-DD format
- room_size can be: "Single", "Double", "Twin", "Triple", "Quad", "Suite", "Studio", "Executive", "Deluxe", "Standard", "Family", "Presidential"
- no_rooms: number of rooms (as integer string)
- adults: number of adults (as integer string)
- child_count: number of children (as integer string)
- child_ages: comma-separated ages (e.g., "3,5,7") or empty string if no children
- room_only: price for room only (as string with no commas, no currency symbols)
- breakfast: price for breakfast (as string with no commas, no currency symbols)
- room_only_type: "Total" or "Per Night" or "Per Person"
- breakfast_type: "Total" or "Per Night" or "Per Person"
- If a field is not visible or not provided, use empty string
- notes: array of strings for any special conditions or additional info

Use this exact structure:

{
  "hotel_name": "",
  "address": "",
  "check_in": "YYYY-MM-DD",
  "check_out": "YYYY-MM-DD",
  "rooms": [
    {
      "room_type": "",
      "room_size": "",
      "no_rooms": "",
      "adults": "",
      "child_count": "",
      "child_ages": "",
      "room_only": "",
      "room_only_type": "Total",
      "breakfast": "",
      "breakfast_type": "Total"
    }
  ],
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

// Function to validate and clean extracted hotel data
function validateExtractedData($data) {
    // Ensure required fields exist
    $requiredFields = ['hotel_name', 'address', 'check_in', 'check_out', 'rooms'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $data[$field] = $field === 'rooms' ? [] : '';
        }
    }
    
    // Validate date formats
    if (!empty($data['check_in']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['check_in'])) {
        $data['check_in'] = '';
    }
    
    if (!empty($data['check_out']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['check_out'])) {
        $data['check_out'] = '';
    }
    
    // Validate rooms array
    if (!is_array($data['rooms'])) {
        $data['rooms'] = [];
    }
    
    // Validate each room
    $validRoomSizes = ['Single', 'Double', 'Twin', 'Triple', 'Quad', 'Suite', 'Studio', 'Executive', 'Deluxe', 'Standard', 'Family', 'Presidential'];
    $validPriceTypes = ['Total', 'Per Night', 'Per Person'];
    
    foreach ($data['rooms'] as &$room) {
        // Ensure all room fields exist
        $roomFields = ['room_type', 'room_size', 'no_rooms', 'adults', 'child_count', 'child_ages', 'room_only', 'room_only_type', 'breakfast', 'breakfast_type'];
        foreach ($roomFields as $field) {
            if (!isset($room[$field])) {
                $room[$field] = '';
            }
        }
        
        // Validate room_size
        if (!empty($room['room_size']) && !in_array($room['room_size'], $validRoomSizes)) {
            $room['room_size'] = '';
        }
        
        // Validate price types
        if (!empty($room['room_only_type']) && !in_array($room['room_only_type'], $validPriceTypes)) {
            $room['room_only_type'] = 'Total';
        }
        
        if (!empty($room['breakfast_type']) && !in_array($room['breakfast_type'], $validPriceTypes)) {
            $room['breakfast_type'] = 'Total';
        }
        
        // Clean numeric fields (remove commas, currency symbols)
        $numericFields = ['no_rooms', 'adults', 'child_count', 'room_only', 'breakfast'];
        foreach ($numericFields as $field) {
            if (!empty($room[$field])) {
                // Remove non-numeric characters except decimal point
                $room[$field] = preg_replace('/[^0-9.]/', '', $room[$field]);
            }
        }
        
        // Validate child ages format
        if (!empty($room['child_ages'])) {
            $ages = explode(',', $room['child_ages']);
            $validAges = [];
            foreach ($ages as $age) {
                $age = trim($age);
                if (is_numeric($age) && $age >= 0 && $age <= 17) {
                    $validAges[] = $age;
                }
            }
            $room['child_ages'] = implode(',', $validAges);
        }
    }
    
    // Ensure notes is an array
    if (!isset($data['notes']) || !is_array($data['notes'])) {
        $data['notes'] = [];
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
    // Image mode - process screenshot
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
    // Text mode - process text input
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