<?php
require '../../server/db_connection.php';
require '../../server/uuid_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);


// ---------------- CONFIGURATION ----------------
// $GEMINI_API_KEY = "AIzaSyDtXWhpsUeWD6fLT8MeikxvgiPkynh2V0o"; // Replace with your actual API key
$GEMINI_API_KEY = trim(file_get_contents('../../gemini-apikey.txt'));// Replace with your actual API key
$GEMINI_MODEL = "gemini-2.0-flash-lite";

// ---------------- GET DATA ----------------
$uuid           = generateUUID();
$category       = $_POST['task_category'] ?? null;
$infoFileName   = $_POST['info_file_name'] ?? null;
$infoDetails    = $_POST['information'] ?? null;
$pastedText     = $_POST['pasted_text'] ?? null;
$workId         = $_POST['work_id'] ?? null;

// ---------------- VALIDATION ----------------
if (!$category || !$workId) {
    echo json_encode(['success' => false, 'message' => 'Category or Work ID missing']);
    exit;
}

// ---------------- GET WORK DIRECTORY ----------------
$stmt = $pdo->prepare("SELECT work_dir_path , uuid , title FROM works WHERE id = ?");
$stmt->execute([$workId]);
$work = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$work || empty($work['work_dir_path'])) {
    echo json_encode(['success' => false, 'message' => 'Work directory not found']);
    exit;
}

if (empty($work['title']) || empty($work['uuid'])) {
    echo json_encode(['success' => false, 'message' => 'Work Title or UUID not found']);
    exit;
}

$workBaseDir = rtrim($work['work_dir_path'], '/');
$taskDirectory = $workBaseDir . '/tasks/' . $uuid;

// ---------------- CREATE DIRECTORIES ----------------
if (!is_dir($taskDirectory)) {
    mkdir($taskDirectory, 0755, true);
}

// ---------------- FILE UPLOAD ----------------
$uploadedFiles = [];
$filesToProcess = []; // Store files for Gemini processing

if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $name) {
        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
            $target = $taskDirectory . '/' . $safeName;

            if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $target)) {
                $uploadedFiles[] = $target;
                $filesToProcess[] = $target; // Add to processing list
            }
        }
    }
}

// ---------------- SAVE PASTED TEXT ----------------
if (!empty($pastedText)) {
    $textFile = $taskDirectory . '/pasted_text.txt';
    file_put_contents($textFile, $pastedText);
    $uploadedFiles[] = $textFile;
}

// ---------------- PROCESS WITH GEMINI AI ----------------
$geminiResponse = null;
$extractedData = null;

if (!empty($filesToProcess)) {
    $geminiResponse = processFilesWithGemini($filesToProcess, $category);

    if ($geminiResponse && isset($geminiResponse['success']) && $geminiResponse['success']) {
        $extractedData = $geminiResponse['data'];
    }
}

// ---------------- SAVE EXTRACTED DATA TO FILE ----------------
if ($extractedData) {
    $dataFile = $taskDirectory . '/extracted_data.json';
    file_put_contents($dataFile, json_encode($extractedData, JSON_PRETTY_PRINT));
    $uploadedFiles[] = $dataFile;
}


// ---------------- FUNCTION: PROCESS WITH GEMINI ----------------
function processFilesWithGemini($files, $category)
{
    global $GEMINI_API_KEY, $GEMINI_MODEL;

    $prompt = getPromptForCategory($category);
    $responses = [];

    foreach ($files as $file) {
        if (!file_exists($file)) continue;

        $mimeType = mime_content_type($file);
        $fileData = base64_encode(file_get_contents($file));

        // Gemini handles PDF, Images, and Text natively via 'inline_data'
        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $fileData
                            ]
                        ]
                    ]
                ]
            ],
            // This forces Gemini to return valid JSON only
            'generationConfig' => [
                'response_mime_type' => 'application/json'
            ]
        ];

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key={$GEMINI_API_KEY}";

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $resultData = json_decode($result, true);
            $extractedText = $resultData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($extractedText) {
                $responses[] = $extractedText;
            }
        }
    }

    return [
        'success' => !empty($responses),
        'data' => $responses
    ];
}

// ---------------- FUNCTION: GET PROMPT FOR CATEGORY ----------------
function getPromptForCategory($category)
{
    if ($category == 1) { // Air Ticket
        return "Extract information from this document and return ONLY valid JSON in this exact format:
        {
            \"applicant_sur_name\": \"\",
            \"applicant_given_name\": \"\",
            \"applicant_salutation\": \"\",
            \"applicant_passport_no\": \"\",
            \"other_applicants\": [
                {
                    \"applicant_sur_name\": \"\",
                    \"applicant_given_name\": \"\",
                    \"applicant_salutation\": \"\",
                    \"applicant_passport_no\": \"\"
                }
            ],
            \"itinerary_information\": [
                {
                    \"departure_from\": \"\",
                    \"departure_at\": \"\",
                    \"arrival_in\": \"\",
                    \"arrival_at\": \"\",
                    \"flight_no\": \"\",
                    \"flight_info\": \"\"
                }
            ],
            \"airline_pnr\": \"\",
            \"galileo_pnr\": \"\",
            \"date_of_issue\": \"\"
        }

        Rules:
        1. Return ONLY the JSON, no other text
        2. If field is not found, leave empty string
        3. For dates, use format: DDMMMYY (e.g., 16DEC25)
        4. If multiple flights, add to itinerary_information array
        5. If multiple passengers, add to other_applicants array";
    } elseif ($category == 2) { // Hotel Booking
        return "Extract information from this hotel booking document and return ONLY valid JSON in this exact format:
        {
            \"hotel_name\": \"\",
            \"hotel_address\": [
                {
                    \"address_line_1\": \"\",
                    \"address_line_2\": \"\",
                    \"address_city\": \"\",
                    \"address_state\": \"\",
                    \"address_zip_code\": \"\"
                }
            ]
            \"hotel_phone_no\": \"\",
            \"hotel_email\": \"\",
            \"hotel_room_no\": \"\",
            \"hotel_room_type\": \"\",
            \"sur_name\": \"\",
            \"given_name\": \"\",
            \"address\": {
                \"present_address\": [
                    {
                        \"address_line_1\": \"\",
                        \"address_line_2\": \"\",
                        \"address_city\": \"\",
                        \"address_state\": \"\",
                        \"address_zip_code\": \"\"
                    }
                ],
                \"permanent_address\": [
                    {
                        \"address_line_1\": \"\",
                        \"address_line_2\": \"\",
                        \"address_city\": \"\",
                        \"address_state\": \"\",
                        \"address_zip_code\": \"\"
                    }
                ]
            },
            \"check_in\": \"\",
            \"check_out\": \"\",
            \"occupancy\": \"\",
            \"room_info\": \"\",
            \"meal_plan\": \"\",
            \"guest_details\": \"\",
            \"no_of_pax\": [
                {
                    \"type\": \"Adult\",
                    \"count\": \"\"
                },
                {
                    \"type\": \"Child\",
                    \"count\": \"\"
                },
                {
                    \"type\": \"Infant\",
                    \"count\": \"\"
                }
            ],
            \"booking_date\": \"\",
            \"cancellation\": \"\",
            \"terms_n_conditions\": \"\",
            \"pcn\": \"\",
            \"hcn\": \"\"
        }

        Rules:
        1. Return ONLY the JSON, no other text
        2. If field is not found, leave empty string
        3. For dates, use format: YYYY-MM-DD
        4. Count should be numbers only
        5. PCN = Portal Confirmation Number
        6. HCN = Hotel Confirmation Number";
    }

    return "Extract all relevant information from this document and return as JSON.";
}

// ---------------- SAVE TO DATABASE ----------------
try {
    // Convert uploaded files to relative paths
    $relativePaths = array_map(function ($path) {
        return str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', $path);
    }, $uploadedFiles);

    $filesJson = json_encode($relativePaths);
    $extractedDataJson = $extractedData ? json_encode($extractedData) : null;

    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            uuid, 
            category, 
            info_file_name, 
            info_details, 
            work_id, 
            work_uuid, 
            work_title, 
            hotel_info, 
            air_ticket_info, 
            all_file_path, 
            status, 
            created_by
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($category == 1) {
        $airTicketInfo = json_encode($extractedData);
        $hotelInfo = null;
    } else {
        $hotelInfo = json_encode($extractedData);
        $airTicketInfo = null;
    }

    $stmt->execute([
        $uuid,
        $category,
        $infoFileName,
        $infoDetails,
        $workId,
        $work['uuid'],
        $work['title'],
        $hotelInfo,
        $airTicketInfo,
        $filesJson,
        'pending',
        'system',
    ]);

    $response = [
        'success' => true,
        'message' => 'Task created successfully',
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
