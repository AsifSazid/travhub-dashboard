<?php
require '../../server/db_connection.php';
require '../../server/uuid_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------------- CONFIGURATION ----------------
$GEMINI_API_KEY = "AIzaSyDtXWhpsUeWD6fLT8MeikxvgiPkynh2V0o"; // Replace with your actual API key
// $GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";
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
$stmt = $pdo->prepare("SELECT work_dir_path FROM works WHERE id = ?");
$stmt->execute([$workId]);
$work = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$work || empty($work['work_dir_path'])) {
    echo json_encode(['success' => false, 'message' => 'Work directory not found']);
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
                // $parsedData = json_decode($extractedText, true);
                // if ($parsedData) {
                //     // $responses[] = $parsedData;
                // }
            }
        }
    }

    return [
        'success' => !empty($responses),
        'data' => mergeResponses($responses, $category)
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

// ---------------- FUNCTION: EXTRACT TEXT FROM FILE ----------------
function extractTextFromFile($filePath)
{
    $mimeType = mime_content_type($filePath);
    $text = '';

    if ($mimeType === 'application/pdf') {
        // For PDF files - you'll need a PDF parser library
        // Example using shell command (requires pdftotext installed)
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
        shell_exec("pdftotext '$filePath' '$tempFile'");
        $text = file_get_contents($tempFile);
        unlink($tempFile);
    } elseif (in_array($mimeType, ['text/plain', 'text/html'])) {
        $text = file_get_contents($filePath);
    } elseif ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        // For DOCX files
        $zip = new ZipArchive;
        if ($zip->open($filePath) === TRUE) {
            $text = $zip->getFromName('word/document.xml');
            $text = strip_tags($text);
            $zip->close();
        }
    }

    return $text ?: "Unable to extract text from file.";
}

// ---------------- FUNCTION: MERGE RESPONSES ----------------
function mergeResponses($responses, $category)
{
    if (empty($responses)) return null;
    if (count($responses) === 1) return $responses[0];

    // For multiple responses, merge intelligently
    $merged = $responses[0];

    for ($i = 1; $i < count($responses); $i++) {
        foreach ($responses[$i] as $key => $value) {
            if (is_array($value)) {
                if ($key === 'other_applicants' || $key === 'itinerary_information') {
                    // Append arrays for Air Ticket
                    if (!isset($merged[$key])) $merged[$key] = [];
                    $merged[$key] = array_merge($merged[$key], $value);
                } elseif (is_array($merged[$key]) && is_array($value)) {
                    // Merge associative arrays
                    $merged[$key] = array_merge($merged[$key], $value);
                }
            } elseif (!empty($value) && empty($merged[$key])) {
                // Fill empty fields
                $merged[$key] = $value;
            }
        }
    }

    return $merged;
}

// ---------------- FUNCTION: SAVE AIR TICKET DATA ----------------
function saveAirTicketData($pdo, $taskId, $data)
{
    try {
        // Save main applicant
        $stmt = $pdo->prepare("
            INSERT INTO air_ticket_applicants 
            (task_id, sur_name, given_name, salutation, passport_no, is_primary) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $taskId,
            $data['applicant_sur_name'] ?? '',
            $data['applicant_given_name'] ?? '',
            $data['applicant_salutation'] ?? '',
            $data['applicant_passport_no'] ?? '',
            1 // is_primary
        ]);

        // Save other applicants
        if (!empty($data['other_applicants'])) {
            foreach ($data['other_applicants'] as $applicant) {
                $stmt = $pdo->prepare("
                    INSERT INTO air_ticket_applicants 
                    (task_id, sur_name, given_name, salutation, passport_no, is_primary) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $taskId,
                    $applicant['applicant_sur_name'] ?? '',
                    $applicant['applicant_given_name'] ?? '',
                    $applicant['applicant_salutation'] ?? '',
                    $applicant['applicant_passport_no'] ?? '',
                    0 // not primary
                ]);
            }
        }

        // Save itinerary
        if (!empty($data['itinerary_information'])) {
            foreach ($data['itinerary_information'] as $itinerary) {
                $stmt = $pdo->prepare("
                    INSERT INTO air_ticket_itinerary 
                    (task_id, departure_from, departure_at, arrival_in, 
                     arrival_at, flight_no, flight_info) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $taskId,
                    $itinerary['departure_from'] ?? '',
                    $itinerary['departure_at'] ?? '',
                    $itinerary['arrival_in'] ?? '',
                    $itinerary['arrival_at'] ?? '',
                    $itinerary['flight_no'] ?? '',
                    $itinerary['flight_info'] ?? ''
                ]);
            }
        }

        // Save PNR info
        $stmt = $pdo->prepare("
            INSERT INTO air_ticket_pnr 
            (task_id, airline_pnr, galileo_pnr, date_of_issue) 
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $taskId,
            $data['airline_pnr'] ?? '',
            $data['galileo_pnr'] ?? '',
            $data['date_of_issue'] ?? ''
        ]);
    } catch (Exception $e) {
        // Log error but don't stop execution
        error_log("Error saving air ticket data: " . $e->getMessage());
    }
}

// ---------------- FUNCTION: SAVE HOTEL BOOKING DATA ----------------
function saveHotelBookingData($pdo, $taskId, $data)
{
    try {
        // Save guest info
        $stmt = $pdo->prepare("
            INSERT INTO hotel_booking_guests 
            (task_id, sur_name, given_name, check_in, check_out, 
             room_type, meal_plan, guest_details, booking_date, 
             pcn, hcn) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $taskId,
            $data['sur_name'] ?? '',
            $data['given_name'] ?? '',
            $data['check_in'] ?? '',
            $data['check_out'] ?? '',
            $data['room_type'] ?? '',
            $data['meal_plan'] ?? '',
            $data['guest_details'] ?? '',
            $data['booking_date'] ?? '',
            $data['pcn'] ?? '',
            $data['hcn'] ?? ''
        ]);

        // Save address
        if (!empty($data['address'])) {
            $addresses = $data['address'];

            // Save present address
            if (!empty($addresses['present_address'])) {
                foreach ($addresses['present_address'] as $address) {
                    $stmt = $pdo->prepare("
                        INSERT INTO hotel_booking_addresses 
                        (task_id, address_type, line_1, line_2, 
                         city, state, zip_code) 
                        VALUES (?, 'present', ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $taskId,
                        $address['address_line_1'] ?? '',
                        $address['address_line_2'] ?? '',
                        $address['address_city'] ?? '',
                        $address['address_state'] ?? '',
                        $address['address_zip_code'] ?? ''
                    ]);
                }
            }

            // Save permanent address
            if (!empty($addresses['permanent_address'])) {
                foreach ($addresses['permanent_address'] as $address) {
                    $stmt = $pdo->prepare("
                        INSERT INTO hotel_booking_addresses 
                        (task_id, address_type, line_1, line_2, 
                         city, state, zip_code) 
                        VALUES (?, 'permanent', ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $taskId,
                        $address['address_line_1'] ?? '',
                        $address['address_line_2'] ?? '',
                        $address['address_city'] ?? '',
                        $address['address_state'] ?? '',
                        $address['address_zip_code'] ?? ''
                    ]);
                }
            }
        }

        // Save pax count
        if (!empty($data['no_of_pax'])) {
            foreach ($data['no_of_pax'] as $pax) {
                $stmt = $pdo->prepare("
                    INSERT INTO hotel_booking_pax 
                    (task_id, pax_type, count) 
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $taskId,
                    $pax['type'] ?? '',
                    $pax['count'] ?? 0
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Error saving hotel booking data: " . $e->getMessage());
    }
}


// ---------------- SAVE TO DATABASE ----------------
// try {
//     // Convert uploaded files to relative paths
//     $relativePaths = array_map(function ($path) {
//         return str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', $path);
//     }, $uploadedFiles);

//     $filesJson = json_encode($relativePaths);
//     $extractedDataJson = $extractedData ? json_encode($extractedData) : null;

//     $stmt = $pdo->prepare("
//         INSERT INTO tasks 
//         (uuid, work_id, category, info_file_name, info_details, 
//          status, created_by, files_json, extracted_data) 
//         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
//     ");

//     $stmt->execute([
//         $uuid,
//         $workId,
//         $category,
//         $infoFileName,
//         $infoDetails,
//         'pending',
//         'system',
//         $filesJson,
//         $extractedDataJson
//     ]);

//     $taskId = $pdo->lastInsertId();

//     // If category is Air Ticket or Hotel Booking, insert into specific tables
//     if ($extractedData) {
//         if ($category == 1) { // Air Ticket
//             saveAirTicketData($pdo, $taskId, $extractedData);
//         } elseif ($category == 2) { // Hotel Booking
//             saveHotelBookingData($pdo, $taskId, $extractedData);
//         }
//     }

//     $response = [
//         'success' => true,
//         'message' => 'Task created successfully',
//         'task_id' => $taskId,
//         'task_uuid' => $uuid,
//         'extracted_data' => $extractedData,
//         'gemini_response' => $geminiResponse
//     ];
// } catch (Exception $e) {
//     $response = [
//         'success' => false,
//         'message' => 'Database error: ' . $e->getMessage()
//     ];
// }

// echo json_encode($response);
