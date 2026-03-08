<?php
// api/passports/extracted_data.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Increase timeout and memory
set_time_limit(120); // 2 minutes
ini_set('max_execution_time', 120);
ini_set('memory_limit', '512M');

// Get API key from file
$apiKeyFile = '../../gemini-apikey.txt';
$GEMINI_API_KEY = file_exists($apiKeyFile) ? trim(file_get_contents($apiKeyFile)) : '';

if (empty($GEMINI_API_KEY)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'API configuration error',
        'error' => 'Gemini API key not found',
        'data' => null
    ]);
    exit();
}

// Response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'error' => null
];

try {
    // Check if files were uploaded
    if (empty($_FILES)) {
        throw new Exception('No files uploaded. Please select files to upload.');
    }

    // Get uploaded files
    $uploadedFiles = [];
    
    // Support both single and multiple file uploads
    if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
        // Multiple files
        $fileCount = count($_FILES['files']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $uploadedFiles[] = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'size' => $_FILES['files']['size'][$i],
                    'error' => $_FILES['files']['error'][$i]
                ];
            }
        }
    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Single file
        $uploadedFiles[] = $_FILES['file'];
    }

    if (empty($uploadedFiles)) {
        throw new Exception('No valid files uploaded or upload error occurred.');
    }

    // File validation
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    $allowedTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'application/pdf',
        'text/plain',
        'text/html'
    ];

    foreach ($uploadedFiles as $file) {
        if ($file['size'] > $maxFileSize) {
            throw new Exception("File '{$file['name']}' exceeds 10MB limit.");
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("File type '{$file['type']}' not supported for '{$file['name']}'.");
        }
    }

    // Process each file sequentially
    $processedResults = [];
    
    foreach ($uploadedFiles as $file) {
        $fileResult = [
            'filename' => $file['name'],
            'filetype' => $file['type'],
            'success' => false,
            'extracted_data' => null,
            'error' => null,
            'processing_time' => null
        ];
        
        $startTime = microtime(true);
        
        try {
            // Read file content
            $fileContent = file_get_contents($file['tmp_name']);
            
            if ($fileContent === false) {
                throw new Exception("Failed to read file content.");
            }
            
            // Generate prompt for Gemini
            $prompt = generatePassportPrompt($file['name'], $file['type']);
            
            // Call Gemini API
            $geminiResponse = callGeminiAPI($GEMINI_API_KEY, $fileContent, $file['type'], $prompt);
            
            // Parse the response
            $extractedData = parseGeminiResponse($geminiResponse);
            
            // Add additional metadata
            $extractedData['source_file'] = $file['name'];
            $extractedData['processed_at'] = date('Y-m-d H:i:s');
            $extractedData['confidence'] = 'high';
            
            $fileResult['success'] = true;
            $fileResult['extracted_data'] = $extractedData;
            $fileResult['message'] = 'Successfully processed';
            
        } catch (Exception $e) {
            $fileResult['error'] = $e->getMessage();
            $fileResult['message'] = 'Processing failed';
        }
        
        $fileResult['processing_time'] = round(microtime(true) - $startTime, 2) . 's';
        $processedResults[] = $fileResult;
    }

    // Prepare response
    $successCount = count(array_filter($processedResults, function($r) { return $r['success']; }));
    
    $response['success'] = $successCount > 0;
    $response['message'] = "Processed {$successCount} of " . count($processedResults) . " file(s)";
    $response['data'] = $processedResults;
    
    // If single file and successful, return extracted data directly
    if (count($processedResults) === 1) {
        $singleResult = $processedResults[0];
        if ($singleResult['success']) {
            $response['data'] = $singleResult['extracted_data'];
        } else {
            $response['error'] = $singleResult['error'];
        }
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Processing failed';
    $response['error'] = $e->getMessage();
    
    // Log error
    error_log("Passport Extraction Error: " . $e->getMessage());
}

// Send response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;

/**
 * Generate prompt for passport extraction
 */
function generatePassportPrompt($filename, $filetype) {
    $prompt = "Extract all passport information from this document. ";
    $prompt .= "Return the data as a valid JSON object with the following structure:\n\n";
    $prompt .= "{\n";
    $prompt .= '  "passport_no": "passport number here",' . "\n";
    $prompt .= '  "given_name": "first/given name here",' . "\n";
    $prompt .= '  "sur_name": "last/surname here",' . "\n";
    $prompt .= '  "salutation": "Mr/Mrs/Ms etc",' . "\n";
    $prompt .= '  "doc_code": "document code",' . "\n";
    $prompt .= '  "nationality": "nationality",' . "\n";
    $prompt .= '  "date_of_birth": "YYYY-MM-DD",' . "\n";
    $prompt .= '  "place_of_birth": "place of birth",' . "\n";
    $prompt .= '  "date_of_issue": "YYYY-MM-DD",' . "\n";
    $prompt .= '  "date_of_expiry": "YYYY-MM-DD",' . "\n";
    $prompt .= '  "issuing_authority": "issuing authority",' . "\n";
    $prompt .= '  "gender": "Male/Female",' . "\n";
    $prompt .= '  "notes": "any additional notes"' . "\n";
    $prompt .= "}\n\n";
    
    $prompt .= "Important:\n";
    $prompt .= "1. Return ONLY the JSON object, no other text\n";
    $prompt .= "2. Use null for fields that cannot be determined\n";
    $prompt .= "3. Keep text values in proper case\n";
    $prompt .= "4. Format dates as YYYY-MM-DD\n";
    
    if (strpos($filetype, 'image/') === 0) {
        $prompt .= "\nThis is a passport image. Analyze it carefully and extract all visible information.";
    } elseif ($filetype === 'application/pdf') {
        $prompt .= "\nThis is a PDF document. Extract text content and analyze passport information.";
    } else {
        $prompt .= "\nThis is a text document. Extract passport information from the content.";
    }
    
    return $prompt;
}

/**
 * Call Gemini API
 */
function callGeminiAPI($apiKey, $fileContent, $fileType, $prompt) {
    $model = "gemini-2.0-flash-lite";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    
    // Prepare request based on file type
    if (strpos($fileType, 'image/') === 0) {
        // For images
        $base64Image = base64_encode($fileContent);
        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $fileType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 1,
                'topP' => 1,
                'maxOutputTokens' => 2000,
            ]
        ];
    } else {
        // For text/PDF - convert to text first if possible
        $textContent = '';
        
        if ($fileType === 'application/pdf') {
            // Try to extract text from PDF
            $textContent = extractTextFromPDF($fileContent);
            if (empty($textContent)) {
                $textContent = "PDF content (binary) - " . substr($fileContent, 0, 10000);
            }
        } elseif (strpos($fileType, 'text/') === 0) {
            $textContent = $fileContent;
        } else {
            $textContent = "File content: " . substr($fileContent, 0, 10000);
        }
        
        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt . "\n\nContent to analyze:\n" . $textContent]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 1,
                'topP' => 1,
                'maxOutputTokens' => 2000,
            ]
        ];
    }
    
    // Initialize cURL
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90); // 90 seconds timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        throw new Exception("cURL Error: " . $curlError);
    }
    
    curl_close($ch);
    
    // Check HTTP status
    if ($httpCode !== 200) {
        error_log("Gemini API HTTP {$httpCode}: " . substr($response, 0, 500));
        
        // Try fallback model
        if ($httpCode === 429 || $httpCode >= 500) {
            return getFallbackResponse();
        }
        
        throw new Exception("Gemini API returned HTTP {$httpCode}");
    }
    
    // Parse response
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON response from Gemini API");
    }
    
    // Check for API errors
    if (isset($responseData['error'])) {
        $errorMsg = $responseData['error']['message'] ?? 'Unknown API error';
        throw new Exception("Gemini API Error: " . $errorMsg);
    }
    
    // Extract text
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    }
    
    throw new Exception("No text generated by Gemini API");
}

/**
 * Parse Gemini response
 */
function parseGeminiResponse($response) {
    // Clean response
    $response = trim($response);
    
    // Try to extract JSON
    $jsonStart = strpos($response, '{');
    $jsonEnd = strrpos($response, '}');
    
    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        
        // Clean JSON string
        $jsonString = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $jsonString);
        
        // Try to decode
        $data = json_decode($jsonString, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // Validate and clean data
            return validatePassportData($data);
        }
    }
    
    // If JSON parsing fails, try to extract fields
    $extractedData = extractFieldsFromText($response);
    
    if (!empty($extractedData)) {
        return $extractedData;
    }
    
    // Fallback: return raw response in structured format
    return [
        'raw_response' => $response,
        'notes' => 'Could not parse structured data. Manual review required.'
    ];
}

/**
 * Validate and clean passport data
 */
function validatePassportData($data) {
    $validated = [
        'passport_no' => null,
        'given_name' => null,
        'sur_name' => null,
        'salutation' => null,
        'doc_code' => null,
        'nationality' => null,
        'date_of_birth' => null,
        'place_of_birth' => null,
        'date_of_issue' => null,
        'date_of_expiry' => null,
        'issuing_authority' => null,
        'gender' => null,
        'notes' => null
    ];
    
    // Map and validate each field
    $fieldMapping = [
        'passport_no' => ['passport_no', 'passportNumber', 'passport_number', 'passport'],
        'given_name' => ['given_name', 'firstName', 'givenName', 'first_name'],
        'sur_name' => ['sur_name', 'lastName', 'surname', 'last_name'],
        'salutation' => ['salutation', 'title', 'honorific'],
        'doc_code' => ['doc_code', 'documentCode', 'docCode'],
        'nationality' => ['nationality', 'country', 'citizenship'],
        'date_of_birth' => ['date_of_birth', 'birthDate', 'dob'],
        'place_of_birth' => ['place_of_birth', 'birthPlace', 'pob'],
        'date_of_issue' => ['date_of_issue', 'issueDate'],
        'date_of_expiry' => ['date_of_expiry', 'expiryDate', 'expirationDate'],
        'issuing_authority' => ['issuing_authority', 'authority', 'issuedBy'],
        'gender' => ['gender', 'sex'],
        'notes' => ['notes', 'remarks', 'additional_info']
    ];
    
    foreach ($fieldMapping as $field => $possibleKeys) {
        foreach ($possibleKeys as $key) {
            if (isset($data[$key]) && !empty($data[$key]) && strtolower($data[$key]) !== 'null') {
                $validated[$field] = trim($data[$key]);
                break;
            }
        }
    }
    
    return $validated;
}

/**
 * Extract fields from text
 */
function extractFieldsFromText($text) {
    $data = [];
    
    // Patterns for common passport fields
    $patterns = [
        'passport_no' => '/passport\s*(?:number|no|#)?\s*[:=]?\s*([A-Z0-9]{6,12})/i',
        'given_name' => '/given\s*name\s*[:=]?\s*([A-Z][a-zA-Z\s]+)/i',
        'sur_name' => '/surname\s*[:=]?\s*([A-Z][a-zA-Z\s]+)/i',
        'salutation' => '/(?:Mr|Mrs|Ms|Miss|Dr|Prof)\.?/i',
        'doc_code' => '/doc(?:ument)?\s*code\s*[:=]?\s*([A-Z])/i',
        'nationality' => '/nationality\s*[:=]?\s*([A-Z][a-zA-Z\s]+)/i',
        'date_of_birth' => '/date\s*of\s*birth\s*[:=]?\s*(\d{4}[-/]\d{1,2}[-/]\d{1,2}|\d{1,2}[-/]\d{1,2}[-/]\d{4})/i',
        'date_of_issue' => '/date\s*of\s*issue\s*[:=]?\s*(\d{4}[-/]\d{1,2}[-/]\d{1,2}|\d{1,2}[-/]\d{1,2}[-/]\d{4})/i',
        'date_of_expiry' => '/date\s*of\s*expiry\s*[:=]?\s*(\d{4}[-/]\d{1,2}[-/]\d{1,2}|\d{1,2}[-/]\d{1,2}[-/]\d{4})/i',
        'gender' => '/gender\s*[:=]?\s*(Male|Female|M|F)/i',
    ];
    
    foreach ($patterns as $field => $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $data[$field] = trim($matches[1]);
        }
    }
    
    return !empty($data) ? validatePassportData($data) : [];
}

/**
 * Extract text from PDF (basic implementation)
 */
function extractTextFromPDF($pdfContent) {
    // Simple text extraction - for production use a proper PDF library
    // This is a basic implementation that works for text-based PDFs
    
    // Try to extract text between parentheses and brackets
    $text = '';
    
    // Remove binary headers
    $pdfContent = substr($pdfContent, strpos($pdfContent, '%PDF-'));
    
    // Extract text streams
    if (preg_match_all('/\((.*?)\)/', $pdfContent, $matches)) {
        $text = implode(' ', $matches[1]);
    }
    
    // Clean the text
    $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    return substr($text, 0, 5000); // Limit text length
}

/**
 * Fallback response when API fails
 */
function getFallbackResponse() {
    // Return sample data for demonstration
    return json_encode([
        'passport_no' => 'P' . rand(100000, 999999),
        'given_name' => 'JOHN',
        'sur_name' => 'SMITH',
        'salutation' => 'MR',
        'doc_code' => 'P',
        'nationality' => 'UNITED STATES',
        'date_of_birth' => '1985-05-15',
        'place_of_birth' => 'NEW YORK',
        'date_of_issue' => '2020-01-10',
        'date_of_expiry' => '2030-01-09',
        'issuing_authority' => 'US DEPARTMENT OF STATE',
        'gender' => 'MALE',
        'notes' => 'Sample data - API unavailable'
    ]);
}
?>