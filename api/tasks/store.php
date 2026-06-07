<?php
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';
require '../../server/make-smb-dir.php';
require_once '../../server/live_storage.php';
require_once '../../server/safe_folder_name.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);


// ---------------- CONFIGURATION ----------------
// $GEMINI_API_KEY = "AIzaSyDtXWhpsUeWD6fLT8MeikxvgiPkynh2V0o"; // Replace with your actual API key
$GEMINI_API_KEY = trim(file_get_contents('../../gemini-apikey.txt')); // Replace with your actual API key
$SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt')); // Replace with your actual API key
$GEMINI_MODEL = "gemini-2.5-flash";
// $GEMINI_MODEL = "gemini-2.0-flash-lite";


// ---------------- File Save ----------------
function fileSaveinSMB($fileName, $filePath){
    $omv = new OMV_SMB_Manager();
    
    $paste_status = $omv->paste_file($fileName, $filePath);
    if ($paste_status === true) {
        $fileStoreInCloud = "File " . $filePath . " Created Successfully <br>";
    } else {
        error_log("❌ SMB Error: " . $paste_status);
    }
}

// ---------------- GET DATA ----------------
$uuid           = generateIDs('tasks');
$taskTitle      = $_POST['task_title'] ?? "No Title Entry";
$category       = $_POST['task_category'] ?? null;
$infoFileName   = $_POST['info_file_name'] ?? null;
$infoDetails    = $_POST['information'] ?? null;
$pastedText     = $_POST['pasted_text'] ?? null;
$workId         = $_POST['work_id'] ?? null;
$taskDate       = $_POST['taskDate'] ?? null;

$rawPerformedBy = $_POST['performedBy'] ?? null;
if (!$rawPerformedBy) {
    echo json_encode(['success' => false, 'message' => 'Task Performer missing']);
    exit;
}
$performedByParts = explode('|', $rawPerformedBy);
$performedBySysID = trim($performedByParts[0]);
$performedByName = trim($performedByParts[1]);

// ---------------- VALIDATION ----------------
if (!$category || !$workId) {
    echo json_encode(['success' => false, 'message' => 'Category or Work ID missing']);
    exit;
}

// ---------------- GET WORK DIRECTORY ----------------
$stmt = $pdo->prepare("SELECT sys_id , title , client_sys_id , client_name FROM com_works WHERE sys_id = ?");
$stmt->execute([$workId]);
$work = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($work['title']) || empty($work['sys_id'])) {
    echo json_encode(['success' => false, 'message' => 'Work Title or UUID not found']);
    exit;
}

// make directory
// Clean folder name parts
$cleanSysId = preg_replace('/\s+/u', '', $uuid['sys_id']);
$clientSysId = preg_replace('/\s+/u', '', $work['client_sys_id']);   
$clientName  = preg_replace('/\s+/u', '', $work['client_name']);     
$workSysId   = preg_replace('/\s+/u', '', $work['sys_id']);          
$workTitle   = preg_replace('/\s+/u', '_', $work['title']);         

$safeWorkTitle = safeFolderName($workTitle);
$safeTaskTitle = safeFolderName($taskTitle);

$taskFolderName = $safeTaskTitle . "+" . $cleanSysId;

// For Server Storage
$clientFolderName = "clients/{$clientSysId}_{$clientName}/{$safeWorkTitle}+{$workSysId}/tasks";
$taskDirectory = makeDir($clientFolderName, $taskFolderName);

// For Cloud Storage
$clientCloudFolderName = "{$SERVER_CUS_PATH}_clients/{$clientSysId}_{$clientName}/{$safeWorkTitle}+{$workSysId}";
$clientCloudFullFolderName = makeSMBDir($clientCloudFolderName, 'tasks');
$fullPath = makeSMBDir($clientCloudFullFolderName, $taskFolderName);

// ---------------- FILE UPLOAD ----------------
$uploadedFiles = [];
$filesToProcess = []; // Store files for Gemini processing

// ---------------- SAVE INFO FILE IF PROVIDED ----------------
if ($infoFileName && $infoDetails) {
    // ফাইলনেম থেকে এক্সটেনশন আলাদা করুন
    $fileExtension = pathinfo($infoFileName, PATHINFO_EXTENSION);
    
    // যদি এক্সটেনশন না থাকে, তাহলে .txt করে দিন
    if (empty($fileExtension)) {
        $infoFileName = $infoFileName . '.txt';
    }
    
    // সেফ ফাইলনেম তৈরি করুন
    $safeInfoFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $infoFileName);
    $infoFilePath = $taskDirectory . '/' . $safeInfoFileName;
    
    // ফাইলে $infoDetails লিখুন
    file_put_contents($infoFilePath, $infoDetails);
    
    // ফাইলটিকে আপলোডেড ফাইলের তালিকায় যোগ করুন
    $uploadedFiles[] = $infoFilePath;
    
    // এই ফাইলটিও Gemini-তে প্রসেস করার জন্য তালিকায় যোগ করুন
    $filesToProcess[] = $infoFilePath;
    
    // ডিবাগিং জন্য (পরবর্তীতে মুছে ফেলবেন)
    error_log("Info file created: " . $infoFilePath);
}

if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $name) {
        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
            $target = $taskDirectory . '/' . $safeName;
            
            if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $target)) {
                $uploadedFiles[] = $target;
                $filesToProcess[] = $target; // Add to processing list
                
                
                fileSaveinSMB($target, $fullPath. '/' . $safeName);
            }
        }
    }
}

// ---------------- SAVE PASTED TEXT ----------------
if (!empty($pastedText)) {
    $textFile = $taskDirectory . '/pasted_text.txt';
    file_put_contents($textFile, $pastedText);
    $uploadedFiles[] = $textFile;
    $filesToProcess[] = $textFile; // এটিকেও Gemini-তে প্রসেস করুন
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

// ---------------- FUNCTION: PROCESS WITH GEMINI ----------------
function processFilesWithGemini($files, $category)
{
    global $GEMINI_API_KEY, $GEMINI_MODEL;

    $prompt = getPromptForCategory($category);
    $responses = [];

    foreach ($files as $file) {
        if (!file_exists($file)) continue;

        // $mimeType = mime_content_type($file);
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file);
        } else {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mime_types = [
                'pdf'  => 'application/pdf',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'txt'  => 'text/plain',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'json' => 'application/json'
            ];
            $mimeType = $mime_types[$extension] ?? 'application/octet-stream';
        }
        
        
        
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
            return "Extract all flight itinerary details from this PDF and return ONLY a JSON object.
                The PDF may contain single or multiple flights, round trips, multi-city journeys, or complex itineraries.
                Ensure you capture ALL flight segments in chronological order.
                
                Use this comprehensive schema:
                {
                  \"purpose\": [
                    {
                      \"route\": \"\", 
                      \"route_type\": \"One-way/Return/Multi-city\",
                      \"passengers\": [],
                      \"travel_date\": \"Departure Date | Arrival Date\",
                      \"others\": []
                    }
                  ],
                
                  \"booking_details\": {
                    \"booking_reference_pnr\": \"\",
                    \"booking_platform\": \"\",
                    \"booking_number\": \"\",
                    \"date_of_issue\": \"YYYY-MM-DD\"
                  },
                
                  \"airline_details\": {
                    \"primary_airline\": \"\",
                    \"airline_pnr\": \"\",
                    \"galileo_pnr\": \"\"
                  },
                
                  \"passengers\": [
                    {
                      \"name\": {\"first\": \"\", \"last\": \"\"},
                      \"full_name\": \"\",
                      \"type\": \"Adult/Child/Infant\",
                      \"ticket_number\": \"\",
                      \"passport_number\": \"\",
                      \"frequent_flyer_number\": \"\",
                      \"seat_assignment\": \"\"
                    }
                  ],
                
                  \"journey\": {
                    \"type\": \"One-way/Return/Multi-city\",
                    \"total_passengers\": 0,
                    \"flights\": [
                      {
                        \"segment_id\": 1,
                        \"flight_number\": \"\",
                        \"operating_airline\": \"\",
                        \"marketing_airline\": \"\",
                        \"departure\": {
                          \"city\": \"\",
                          \"airport\": \"\",
                          \"airport_code\": \"\",
                          \"terminal\": \"\",
                          \"date\": \"YYYY-MM-DD\",
                          \"time\": \"HH:MM\",
                          \"full_datetime\": \"\"
                        },
                        \"arrival\": {
                          \"city\": \"\",
                          \"airport\": \"\",
                          \"airport_code\": \"\",
                          \"terminal\": \"\",
                          \"date\": \"YYYY-MM-DD\",
                          \"time\": \"HH:MM\",
                          \"full_datetime\": \"\"
                        },
                        \"duration\": \"\",
                        \"class\": \"\",
                        \"status\": \"\",
                        \"aircraft\": \"\",
                        \"meal\": \"\",
                        \"stops\": 0,
                        \"stopover_info\": [],
                        \"baggage_info\": {
                          \"checked\": \"\",
                          \"cabin\": \"\",
                          \"personal_item\": \"\",
                          \"details\": \"\"
                        },
                        \"special_services\": \"\"
                      }
                    ],
                
                    \"transfers\": [
                      {
                        \"from_flight\": 1,
                        \"to_flight\": 2,
                        \"transfer_location\": \"\",
                        \"transfer_duration\": \"\",
                        \"transfer_notes\": \"\",
                        \"baggage_checked_through\": true
                      }
                    ]
                  },
                
                  \"baggage_allowance\": {
                    \"summary\": \"\",
                    \"per_passenger\": [
                      {
                        \"passenger_name\": \"\",
                        \"checked_baggage\": \"\",
                        \"cabin_baggage\": \"\",
                        \"personal_item\": \"\",
                        \"total_weight_allowance\": \"\",
                        \"restrictions\": \"\"
                      }
                    ]
                  },
                
                  \"fare_details\": {
                    \"base_fare\": {\"amount\": 0, \"currency\": \"\"},
                    \"taxes\": {\"amount\": 0, \"breakdown\": []},
                    \"total_fare\": {\"amount\": 0, \"currency\": \"\"},
                    \"fare_rules\": {
                      \"refundable\": true,
                      \"changeable\": true,
                      \"cancellation_penalty\": \"\",
                      \"validity\": \"\"
                    }
                  },
                
                  \"important_notes\": [
                    {
                      \"type\": \"check-in/visa/baggage/other\",
                      \"message\": \"\"
                    }
                  ],
                
                  \"raw_extracted_text\": \"\"
                }
                
                --- PNR IDENTIFICATION RULES ---
                
                There are TWO distinct types of reservation references in airline tickets:
                
                1. galileo_pnr (GDS PNR / Reservation Code / Booking Reference):
                   - This is the reference code issued by the Global Distribution System (GDS) or travel agency system.
                   - Common labels: \"RESERVATION CODE\", \"BOOKING REF\", \"PNR\", \"GDS PNR\", \"REC LOC\", \"REFERENCE\", \"BOOKING REFERENCE\"
                   - Typically found near the travel agency information or at the top of the document.
                
                2. airline_pnr (Airline Booking Code / Airline Confirmation Code):
                   - This is the reference code issued directly by the operating/marketing airline.
                   - Common labels: \"AIRLINE RES CODE\", \"AIRLINE BOOKING CODE\", \"AIRLINE PNR\", \"CARRIER PNR\", \"CONFIRMATION CODE\", \"AIRLINE RESERVATION CODE\"
                   - Typically found near the flight segment details.
                
                CRITICAL: Identify these codes based on their LABELS and CONTEXT. These are DIFFERENT codes. Do NOT swap them. If only one code exists, populate only that field and leave the other as null.
                
                --- IMPORTANT INSTRUCTIONS ---
                
                1. Extract ALL flight segments in correct chronological order
                
                2. For multi-city trips, include all segments sequentially
                
                3. Capture transfer information between connecting flights where applicable
                
                4. Include baggage allowance per passenger if specified
                
                5. Extract fare details if available
                
                6. Note any special conditions or restrictions
                
                7. Include passenger details with full names
                
                8. For airport_code: If IATA code is not explicitly present, infer it from the airport name or city where possible. If inference is not possible, leave as null.
                
                9. For fare_rules: Analyze endorsement/restrictions text. Keywords like \"NON REF\", \"NONEND\", \"NON REFUNDABLE\" indicate refundable: false. Keywords like \"NON CHANGE\", \"NON ENDORSABLE\" indicate changeable: false.
                
                10. For passenger type: Default to \"Adult\" unless explicitly stated as \"Child\" or \"Infant\".
                
                11. For transfers: Create transfer objects only when there are connecting flights with stopovers. For direct flights, keep empty array.
                
                --- PURPOSE FIELD RULES ---
                        
                12. \"purpose\" must be a summarized version of the itinerary:

                    - \"route\": Combine all flight segments in chronological order using hyphen (-) as separator.
                        * One-way example: \"DAC-SIN\"
                        * Return example: \"DAC-SIN-DAC\" (outbound AND inbound both included)
                        * Multi-city example: \"DAC-SIN-DPS\"
                        * For airport_code: If IATA 3-letter code (e.g., FCO, DAC) is explicitly present in the document, use that.
                        * If IATA code is NOT present, use the full city name (e.g., ROME, DHAKA).
                        * Do NOT use abbreviations like ROM unless it is the official IATA code.
                        * Return trip example with IATA codes: \"FCO-DAC-FCO\"
                        * Return trip example with city names: \"ROME-DHAKA-ROME\"
                        * Do NOT use slash (/) as separator. Only hyphen (-).
                    
                    - \"route_type\": Automatically detect based on segments:
                        * One-way: Different departure and arrival cities with no return to origin
                        * Return: Returns to original departure city
                        * Multi-city: Multiple segments but does not return to origin
                    
                    - \"passengers\": This MUST be an array of STRINGS, not objects.
                        * Parse name from format like \"LAST/FIRST MIDDLE TITLE\" or \"LAST/FIRST TITLE\"
                        * Example: \"RAHMAN/MD MAHFUZUR MR\" 
                            - Last Name = everything before /
                            - First Name = first part after / (excluding title)
                            - Salutation = title at the end (MR, MRS, MS, etc.)
                        * Format output as: \"Salutation FirstName LastName\"
                        * Example output: \"Mr. MD MAHFUZUR RAHMAN\"
                        * Remove any slashes (/) from final output
                        * Convert salutation: MR → Mr., MRS → Mrs., MS → Ms., MISS → Miss, DR → Dr.
                        * Extract from fields like \"PREPARED FOR\" or passenger name sections
                    
                    - \"travel_date\": First departure date | Final arrival date
                        * Format: \"DD MMM | DD MMM\" (e.g., \"14 Apr | 30 Jun\")
                        * Use full date format from document
                    
                    - \"others\": This MUST be an array of strings with items in this exact format and order:
                        * First: \"GDS: {galileo_pnr}\" - from \"RESERVATION CODE\" or \"BOOKING REF\"
                        * Second: \"Airline PNR: {airline_pnr}\" - from \"AIRLINE RES CODE\" or \"AIRLINE BOOKING CODE\"
                        * Subsequent: \"Ticket: {ticket_number}\" for each ticket number found
                        * If any item is missing, then show Not Found but preserve the order
                        * Example: [\"GDS: UTGZNT\" | \"Airline PNR: EVUVQL\" | \"9975853248975, 9975853248975\"]

                
                13. If multiple separate bookings exist, create multiple objects inside \"purpose\" array
                
                ---
                
                If any field is not found in the PDF, use empty string for strings, 0 for numbers, [] for arrays, and null for objects where applicable.";

    } elseif ($category == 2) { // Hotel Booking
                    //     \"purpose\": [
                    //     {
                    //       \"route\": \"\", 
                    //       \"route_type\": \"One-way/Return/Multi-city\",
                    //       \"passengers\": [],
                    //       \"travel_date\": \"Departure Date | Arrival Date\",
                    //       \"others\": []
                    //     }
                    // ],
        return "Extract all details from this hotel voucher PDF and return ONLY a JSON object. 
               If the PDF contains an image of the hotel or a logo, try to identify the hotel image URL if mentioned, 
               otherwise leave the 'hotel_image_url' empty.
               Use this exact schema:
                {
                    \"hotel_name\": \"\",
                    \"hotel_city\": \"\",
                    \"hotel_country\": \"\",
                    \"hotel_address\": [
                        {
                            \"address_line_1\": \"\",
                            \"address_line_2\": \"\",
                            \"address_city\": \"\",
                            \"address_state\": \"\",
                            \"address_zip_code\": \"\"
                        }
                    ]
                    \"hotel_phone\": \"\",
                    \"hotel_email\": \"\",
                    \"hotel_room_no\": \"\",
                    \"room_type\": \"\",
                    \"total_rooms\": 1,
                    \"guest_names\": [],
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
                    \"check_in_date\": \"\",
                    \"check_out_date\": \"\",
                    \"no_of_nights\": \"\",
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
                    
                    \"booking_id\": \"\",
                    \"hotel_image_url\": \"\",
                    \"cancellation_policy\": \"\",
                    \"total_price\": \"\",
                    \"currency\": \"\"
                }
        
                Rules:
                1. Return ONLY the JSON, no other text
                2. If field is not found, leave empty string
                3. For dates, use format: YYYY-MM-DD
                4. Count should be numbers only
                5. PCN = Portal Confirmation Number
                6. HCN = Hotel Confirmation Number
                7. DON'T GIVE GUESS OR HALLUCINATE RESPONSE";
    }

    return "Extract all relevant information from this document and return as JSON.";
}

// ---------------- SAVE TO DATABASE ----------------
try {
    // Convert uploaded files to relative paths
    $relativePaths = array_map(function ($path) {
        // return str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', $path);
        return basename($path);
    }, $uploadedFiles);

    $filesJson = json_encode($relativePaths);
    $extractedDataJson = $extractedData ? json_encode($extractedData) : null;

    $metaDataJson = buildMetaData(
        null,
        $_SESSION['user_name'] ?? 'system'
    );

    $metaData = json_decode($metaDataJson, true);

    // যদি $taskDate এ data থাকে
    if (!empty($taskDate)) {
        $metaData['created_by_date']['date'] = $taskDate;
    }

    // আবার array → JSON string
    $metaDataJson = json_encode($metaData);

    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            uuid, 
            sys_id, 
            title, 
            category, 
            info_file_name, 
            info_details, 
            work_sys_id, 
            work_title, 
            hotel_info, 
            air_ticket_info, 
            all_file_name, 
            status, 
            performed_by,
            meta_data
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($category == 1) {
        $airTicketInfo = json_encode($extractedData);
        $hotelInfo = null;
    } else {
        $hotelInfo = json_encode($extractedData);
        $airTicketInfo = null;
    }

    $stmt->execute([
        $uuid['uuid'],
        $uuid['sys_id'],
        $taskTitle,
        $category,
        $infoFileName,
        $infoDetails,
        $work['sys_id'],
        $work['title'],
        $hotelInfo,
        $airTicketInfo,
        $filesJson,
        'pending',
        $rawPerformedBy,
        $metaDataJson,
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
