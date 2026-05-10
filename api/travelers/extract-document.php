<?php
/**
 * Document Extraction API for Traveler Creation
 * Extracts detailed passport/NID data using Gemini AI
 * Returns data with dates in DD-MM-YYYY format
 */

require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/make-dir.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get Gemini API Key
$GEMINI_API_KEY = trim(file_get_contents('../../gemini-apikey.txt'));
if (empty($GEMINI_API_KEY)) {
    echo json_encode(['success' => false, 'message' => 'Gemini API key not configured']);
    exit;
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$documentType = $_POST['document_type'] ?? 'passport';
if (!in_array($documentType, ['passport', 'nid'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid document type. Must be passport or nid']);
    exit;
}

// Save uploaded file to tmp directory
$tmpDir = '../../tmp/';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0777, true);
}

$file = $_FILES['file'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, PDF, WebP']);
    exit;
}

$safeFileName = 'tmp_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
$tmpFilePath = $tmpDir . $safeFileName;

if (!move_uploaded_file($file['tmp_name'], $tmpFilePath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
    exit;
}

try {
    if ($fileExtension === 'pdf') {
        $extractedData = handlePDFExtraction($tmpFilePath, $GEMINI_API_KEY, $documentType);
    } else {
        $extractedData = handleImageExtraction($tmpFilePath, $fileExtension, $GEMINI_API_KEY, $documentType);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Document extracted successfully',
        'data' => $extractedData['display_data'],
        'full_extracted_data' => $extractedData['full_data'],
        'file_path' => $tmpFilePath,
        'document_type' => $documentType
    ]);
    
} catch (Exception $e) {
    if (file_exists($tmpFilePath)) {
        unlink($tmpFilePath);
    }
    
    error_log('Document Extraction Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Extraction failed: ' . $e->getMessage()
    ]);
}

/**
 * Custom MIME detection using file magic bytes
 */
function detectMimeType($filePath) {
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        return 'image/jpeg';
    }
    
    $bytes = fread($handle, 12);
    fclose($handle);
    
    $hex = strtoupper(bin2hex($bytes));
    
    if (strpos($hex, '89504E47') === 0) return 'image/png';
    if (strpos($hex, 'FFD8FF') === 0) return 'image/jpeg';
    if (strpos($hex, '52494646') === 0 && strpos($hex, '57454250') !== false) return 'image/webp';
    if (strpos($hex, '47494638') === 0) return 'image/gif';
    if (strpos($hex, '424D') === 0) return 'image/bmp';
    
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeMap = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'webp' => 'image/webp',
        'gif' => 'image/gif', 'bmp' => 'image/bmp'
    ];
    
    return $mimeMap[$ext] ?? 'image/jpeg';
}

function handleImageExtraction($filePath, $fileExtension, $apiKey, $documentType) {
    $mimeType = detectMimeType($filePath);
    
    if (!is_readable($filePath)) {
        throw new Exception('Cannot read uploaded file');
    }
    
    $imageData = base64_encode(file_get_contents($filePath));
    
    if (empty($imageData)) {
        throw new Exception('Failed to encode image data');
    }
    
    $prompt = buildExtractionPrompt($documentType);
    
    return callGeminiAPI($apiKey, $prompt, $mimeType, $imageData, $documentType);
}

function handlePDFExtraction($pdfPath, $apiKey, $documentType) {
    if (!extension_loaded('imagick')) {
        throw new Exception('PDF processing requires Imagick PHP extension');
    }
    
    try {
        $imagick = new Imagick();
        $imagick->setResolution(150, 150);
        
        if (!is_readable($pdfPath)) {
            throw new Exception('Cannot read PDF file');
        }
        
        $imagick->readImage($pdfPath);
        $numPages = $imagick->getNumberImages();
        
        if ($numPages === 0) {
            throw new Exception('PDF has no pages');
        }
        
        $pagesToProcess = min($numPages, 3);
        error_log("PDF has {$numPages} pages, processing first {$pagesToProcess} pages");
        
        $parts = [["text" => buildExtractionPrompt($documentType, true)]];
        
        for ($i = 0; $i < $pagesToProcess; $i++) {
            $imagick->setIteratorIndex($i);
            $imagick->setImageFormat('png');
            
            $pageImageData = base64_encode($imagick->getImageBlob());
            
            if (empty($pageImageData)) continue;
            
            $parts[] = [
                "inline_data" => [
                    "mime_type" => "image/png",
                    "data" => $pageImageData
                ]
            ];
        }
        
        $imagick->clear();
        $imagick->destroy();
        
        if (count($parts) <= 1) {
            throw new Exception('Failed to extract any images from PDF');
        }
        
        return executeGeminiRequest($apiKey, $parts, $documentType);
        
    } catch (ImagickException $e) {
        throw new Exception('PDF processing failed: ' . $e->getMessage());
    }
}

function buildExtractionPrompt($documentType, $isMultiPage = false) {
    $multiPageNote = $isMultiPage ? "This document has multiple pages. Look through ALL pages to find the information. " : "";
    
    if ($documentType === 'passport') {
        return $multiPageNote . "Analyze this passport document image carefully. Extract ALL information visible on the passport bio-data page.

IMPORTANT:
- The bio-data page contains the photo, personal details, and MRZ
- MRZ (Machine Readable Zone) is at the bottom with two lines of text
- ALL dates MUST be in DD-MM-YYYY format (e.g., 31-12-1983, 16-12-1989)
- Convert any other date format you see to DD-MM-YYYY

CLASSIFY AS: bio_page

Extract these fields exactly:
{
    \"page_type\": \"bio_page\",
    \"bio_info\": {
        \"passport_number\": \"Extract from top of page or MRZ line 2 (first 9 chars before <)\",
        \"country_code\": \"3-letter code (BGD, IND, USA etc.)\",
        \"surname\": \"Surname/Last name from MRZ\",
        \"given_names\": \"Given names from MRZ\",
        \"full_name\": \"Complete name: GIVEN_NAMES SURNAME\",
        \"nationality\": \"Full nationality word (BANGLADESHI, INDIAN etc.)\",
        \"date_of_birth\": \"DD-MM-YYYY format\",
        \"sex\": \"M or F\",
        \"place_of_birth\": \"Place of birth city\",
        \"date_of_issue\": \"DD-MM-YYYY format\",
        \"date_of_expiry\": \"DD-MM-YYYY format\",
        \"issuing_authority\": \"Issuing authority name\",
        \"father_name\": \"Father's name\",
        \"mother_name\": \"Mother's name\",
        \"spouse_name\": \"Spouse name if shown\",
        \"permanent_address\": \"Complete address\",
        \"emergency_contact\": {
            \"name\": \"\",
            \"relationship\": \"\",
            \"address\": \"\",
            \"telephone\": \"\"
        },
        \"mrz_line_1\": \"Complete MRZ line 1\",
        \"mrz_line_2\": \"Complete MRZ line 2\"
    }
}

Return ONLY valid JSON, no other text.";
    } else {
        return $multiPageNote . "Analyze this National ID (NID) card image carefully.

IMPORTANT: ALL dates MUST be in DD-MM-YYYY format (e.g., 16-12-1989)

DETERMINE if this is nid_front or nid_back, then extract:

For nid_front:
{
    \"page_type\": \"nid_front\",
    \"nid_info\": {
        \"nid_number\": \"Full NID number\",
        \"full_name_en\": \"Name in English\",
        \"full_name_bn\": \"Name in Bengali\",
        \"father_name_en\": \"Father's name in English\",
        \"father_name_bn\": \"Father's name in Bengali\",
        \"mother_name_en\": \"Mother's name in English\",
        \"mother_name_bn\": \"Mother's name in Bengali\",
        \"date_of_birth\": \"DD-MM-YYYY format\",
        \"date_of_birth_bn\": \"\",
        \"photo_present\": \"true\"
    }
}

For nid_back:
{
    \"page_type\": \"nid_back\",
    \"nid_info\": {
        \"nid_number\": \"Full NID number\",
        \"full_name_en\": \"\",
        \"full_name_bn\": \"\",
        \"permanent_address_en\": \"\",
        \"permanent_address_bn\": \"\",
        \"place_of_birth_en\": \"\",
        \"place_of_birth_bn\": \"\",
        \"date_of_issue\": \"DD-MM-YYYY\",
        \"blood_group\": \"\"
    }
}

Return ONLY valid JSON, no other text.";
    }
}

function callGeminiAPI($apiKey, $prompt, $mimeType, $imageData, $documentType) {
    $parts = [
        ["text" => $prompt],
        ["inline_data" => ["mime_type" => $mimeType, "data" => $imageData]]
    ];
    
    return executeGeminiRequest($apiKey, $parts, $documentType);
}

function executeGeminiRequest($apiKey, $parts, $documentType) {
    $model = 'gemini-2.0-flash-lite';
    
    $payload = [
        "contents" => [[
            "parts" => $parts
        ]],
        "generationConfig" => [
            "response_mime_type" => "application/json",
            "temperature" => 0.1,
            "maxOutputTokens" => 1024
        ]
    ];
    
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Curl error: ' . $error);
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : "HTTP {$httpCode}";
        throw new Exception("Gemini API error: " . $errorMsg);
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Invalid API response structure");
    }
    
    $rawText = $result['candidates'][0]['content']['parts'][0]['text'];
    
    $cleanJson = preg_replace('/^```json\s*|\s*```$/m', '', $rawText);
    $cleanJson = trim($cleanJson);
    
    $extractedData = json_decode($cleanJson, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to parse JSON: " . json_last_error_msg());
    }
    
    // Convert all dates to DD-MM-YYYY
    $extractedData = convertAllDates($extractedData);
    
    $displayData = buildDisplayData($extractedData, $documentType);
    
    return [
        'display_data' => $displayData,
        'full_data' => $extractedData
    ];
}

/**
 * Convert all dates in extracted data to DD-MM-YYYY format
 */
function convertAllDates($data) {
    $dateFields = [
        'date_of_birth',
        'date_of_issue',
        'date_of_expiry',
        'date_of_birth_bn'
    ];
    
    array_walk_recursive($data, function(&$value, $key) use ($dateFields) {
        if (in_array($key, $dateFields) && !empty($value) && is_string($value)) {
            $value = convertDateFormat($value);
        }
    });
    
    return $data;
}

/**
 * Convert various date formats to DD-MM-YYYY
 */
function convertDateFormat($dateString) {
    if (empty($dateString)) {
        return '';
    }
    
    $dateString = trim($dateString);
    
    // Already in DD-MM-YYYY format?
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateString)) {
        return $dateString;
    }
    
    $formats = [
        'd M Y',      // 16 Dec 1989
        'd F Y',      // 16 December 1989
        'd-M-Y',      // 16-Dec-1989
        'd/m/Y',      // 16/12/1989
        'd-m-Y',      // 16-12-1989
        'd.m.Y',      // 16.12.1989
        'Y-m-d',      // 1989-12-16
        'Y/m/d',      // 1989/12/16
        'm/d/Y',      // 12/16/1989
        'M d, Y',     // Dec 16, 1989
        'F d, Y',     // December 16, 1989
    ];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateString);
        if ($date && $date->format($format) === $dateString) {
            return $date->format('d-m-Y');
        }
    }
    
    // Try strtotime
    $timestamp = strtotime($dateString);
    if ($timestamp !== false && $timestamp > 0) {
        return date('d-m-Y', $timestamp);
    }
    
    // Return original if can't parse
    error_log("Could not parse date: {$dateString}");
    return $dateString;
}

/**
 * Build simplified display data for form fields
 */
function buildDisplayData($fullData, $documentType) {
    $display = [
        'full_name' => '',
        'date_of_birth' => '',
        'document_number' => '',
        'page_type' => $fullData['page_type'] ?? ''
    ];
    
    if ($documentType === 'passport' && isset($fullData['bio_info'])) {
        $bio = $fullData['bio_info'];
        $display['full_name'] = $bio['full_name'] ?? '';
        $display['date_of_birth'] = $bio['date_of_birth'] ?? '';
        $display['document_number'] = $bio['passport_number'] ?? '';
        
    } elseif ($documentType === 'nid' && isset($fullData['nid_info'])) {
        $nid = $fullData['nid_info'];
        $display['full_name'] = $nid['full_name_en'] ?? $nid['full_name_bn'] ?? '';
        $display['date_of_birth'] = $nid['date_of_birth'] ?? '';
        $display['document_number'] = $nid['nid_number'] ?? '';
    }
    
    return $display;
}
?>