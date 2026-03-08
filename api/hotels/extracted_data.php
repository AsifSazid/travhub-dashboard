<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

set_time_limit(120);

$response = [
    'success' => false,
    'data'    => [],
    'error'   => null
];

try {

    /* ================= FILE COLLECTION ================= */
    $files = [];

    // multiple files (input name="files[]")
    if (!empty($_FILES['files'])) {
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $files[] = [
                    'tmp_name' => $tmp,
                    'type'     => $_FILES['files']['type'][$i]
                ];
            }
        }
    }
    // single file (input name="file")
    elseif (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $files[] = [
            'tmp_name' => $_FILES['file']['tmp_name'],
            'type'     => $_FILES['file']['type']
        ];
    }

    if (empty($files)) {
        throw new Exception('No valid file uploaded');
    }

    /* ================= PROCESS EACH FILE ================= */
    foreach ($files as $index => $file) {

        $content = file_get_contents($file['tmp_name']);
        if (!$content) {
            continue;
        }

        $prompt     = hotelPrompt();
        $geminiText = callGemini($content, $file['type'], $prompt);
        
        // var_dump($geminiText);
        // die;
        
        $parsedData = parseGeminiJSON($geminiText);

        $response['data'][] = [
            'file_index' => $index,
            'file_type'  => $file['type'],
            'result'     => $parsedData
        ];
    }

    if (empty($response['data'])) {
        throw new Exception('Failed to process uploaded files');
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
exit;


/* =======================================================
   PROMPT
======================================================= */
function hotelPrompt() {
    return <<<PROMPT
CRITICAL INSTRUCTION: Extract data ONLY from the provided document. 
Do NOT use placeholder names like "John Smith" or "Jane Doe". 
If you cannot find a specific value, leave it as an empty string "".
Don't use PIN No as HCN. If you don't find any HCN/Hotel Confirmation Number, then leave as empty string or "".

Extract information from this hotel booking document and return ONLY valid JSON in this exact structure:

{
    "hotel_name": "",
    "hotel_address": [
        {
            "address_line_1": "",
            "address_line_2": "",
            "address_city": "",
            "address_state": "",
            "address_zip_code": ""
        }
    ],
    "hotel_phone_no": "",
    "hotel_email": "",
    "hotel_room_no": "",
    "hotel_room_type": "",
    "sur_name": "",
    "given_name": "",
    "check_in": "",
    "check_out": "",
    "occupancy": "",
    "room_info": "",
    "meal_plan": "",
    "guest_details": "",
    "no_of_pax": [
        { "type": "Adult", "count": 0 },
        { "type": "Child", "count": 0 },
        { "type": "Infant", "count": 0 }
    ],
    "booking_date": "",
    "cancellation": "",
    "terms_n_conditions": "",
    "pcn": "",
    "hcn": ""
}

Rules:
1. Return ONLY JSON, no markdown formatting (no ```json).
2. Dates MUST be in YYYY-MM-DD format.
3. PCN = Booking Number / Reference.
4. HCN = Confirmation Number / ID.
5. Strictly NO fictional data.
PROMPT;
}


/* =======================================================
   GEMINI API CALL
======================================================= */
function callGemini($content, $type, $prompt) {
    $apiKey = trim(file_get_contents('../../gemini-apikey.txt'));
    
    // Gemini 2.0 Flash Lite handles multimodal data well
    $model = 'gemini-2.0-flash-lite';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$apiKey";

    // Prepare payload for Multimodal (Image/PDF)
    $payload = [
        'contents' => [[
            'parts' => [
                ['text' => $prompt],
                [
                    'inline_data' => [
                        'mime_type' => $type,
                        'data'      => base64_encode($content)
                    ]
                ]
            ]
        ]],
        'generationConfig' => [
            'response_mime_type' => 'application/json' // Force JSON response
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 90
    ]);

    $response = curl_exec($ch);
    if (!$response) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);

    $json = json_decode($response, true);
    
    if (isset($json['error'])) {
        throw new Exception("Gemini API Error: " . $json['error']['message']);
    }

    return $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
}


/* =======================================================
   PARSE GEMINI JSON SAFELY
======================================================= */
function parseGeminiJSON($text) {

    $start = strpos($text, '{');
    $end   = strrpos($text, '}');

    if ($start === false || $end === false) {
        throw new Exception('Invalid Gemini response format');
    }

    $jsonString = substr($text, $start, $end - $start + 1);
    $decoded    = json_decode($jsonString, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decode failed');
    }

    return $decoded;
}
