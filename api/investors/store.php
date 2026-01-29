<?php
require '../../server/db_connection.php';
require '../../server/uuid_generator.php';
require '../../server/investor_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Main function to handle the request
function handleRequest() {
    global $pdo;
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
        return;
    }
    
    try {
        // Check if files are uploaded (FormData approach)
        if (!empty($_FILES['files'])) {
            handleFormDataRequest();
        } else {
            handleJsonRequest();
        }
    } catch (Exception $e) {
        error_log('Error in store.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        sendError($e->getMessage(), 500);
    }
}

// Handle FormData request (with file upload)
function handleFormDataRequest() {
    global $pdo;
    
    // Get investor data from POST
    $investorData = json_decode($_POST['investor_data'], true);
    
    if (!$investorData) {
        sendError('No investor data received', 400);
        return;
    }
    
    // Validate required fields
    validateRequiredFields($investorData);
    
    // Use default user since no authentication needed
    $createdBy = 'system_admin';
    $createdById = 0;
    
    // Generate meta data
    $metaDataJson = buildMetaData(null, $createdBy);
    
    // Extract investor info with defaults
    $dateOfInvesting = $investorData['date_of_investing'] ?? date('Y-m-d');
    $percentage = $investorData['percentage'] ?? 0;
    $fullName = $investorData['full_name'] ?? '';
    $phone = $investorData['phone']['primary_no'] ?? '';
    $email = $investorData['email']['primary'] ?? '';
    
    // Generate UUID and investor ID
    $uuid = generateUUID();
    $sys_id = generateInvestorId($dateOfInvesting);
    
    // Create investor folder
    $cleanSysId = preg_replace('/\s+/u', '', $sys_id);
    $cleanFullName = preg_replace('/\s+/u', '', $fullName);
    $investorFolderName = $cleanSysId . '_' . $cleanFullName;
    
    // Create directory structure
    $basePath = "../../uploads/investors/";
    $investorFolderPath = $basePath . $investorFolderName;
    
    makeDir('investors', $investorFolderName);
    
    // Handle file uploads
    $uploadedFiles = [];
    $files = $_FILES['files'];
    
    if (isset($files['name'][0]) && !empty($files['name'][0])) {

        // Ensure investor directory exists
        if (!file_exists($investorFolderPath)) {
            mkdir($investorFolderPath, 0777, true);
        }
    
        // Allowed file types
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];
    
        // Maximum file size (5MB)
        $maxFileSize = 5 * 1024 * 1024;
    
        // Process each file
        for ($i = 0; $i < count($files['name']); $i++) {
    
            $fileName  = $files['name'][$i];
            $fileTmp   = $files['tmp_name'][$i];
            $fileSize  = $files['size'][$i];
            $fileType  = $files['type'][$i];
            $fileError = $files['error'][$i];
    
            // Skip if error
            if ($fileError !== UPLOAD_ERR_OK) {
                continue;
            }
    
            // Validate file type
            if (!isset($allowedTypes[$fileType])) {
                continue;
            }
    
            // Validate file size
            if ($fileSize > $maxFileSize) {
                continue;
            }
    
            // Get file extension
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
            // Filename rule
            if ($i === 0) {
                $baseName = 'profile-pic';
            } else {
                $baseName = 'image-' . $i;
            }
    
            // Handle duplicate filenames
            $counter = 1;
            $finalName = $baseName . '.' . $fileExtension;
            $destination = $investorFolderPath . '/' . $finalName;
    
            while (file_exists($destination)) {
                $counter++;
                $finalName = $baseName . '-' . $counter . '.' . $fileExtension;
                $destination = $investorFolderPath . '/' . $finalName;
            }
    
            // Move uploaded file
            if (move_uploaded_file($fileTmp, $destination)) {
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'stored_name'   => $finalName,
                    'file_type'     => $fileType,
                    'file_size'     => $fileSize,
                    'file_path'     => "investors/{$investorFolderName}/{$finalName}",
                    'upload_date'   => date('Y-m-d H:i:s')
                ];
            }
        }
    }
    
    // Prepare phone data for JSON storage
    $phoneData = [
        'primary_no' => $phone
    ];
    
    // Add secondary phones if exists
    if (!empty($investorData['phone']['secondary_no'])) {
        $phoneData['secondary_no'] = $investorData['phone']['secondary_no'];
    }
    
    // Prepare email data for JSON storage
    $emailData = [
        'primary' => $email
    ];
    
    // Add secondary emails if exists
    if (!empty($investorData['email']['secondary'])) {
        $emailData['secondary'] = $investorData['email']['secondary'];
    }
    
    // Prepare address data for JSON storage
    $addressData = [];
    if (!empty($investorData['address'])) {
        $addressData = [
            'address_line_1' => $investorData['address']['address_line_1'] ?? '',
            'address_line_2' => $investorData['address']['address_line_2'] ?? '',
            'city' => $investorData['address']['city'] ?? '',
            'state' => $investorData['address']['state'] ?? '',
            'zip_code' => $investorData['address']['zip_code'] ?? '',
            'country' => $investorData['address']['country'] ?? ''
        ];
    }
    
    // Prepare emergency contact data for JSON storage
    $emergencyContactData = [];
    if (!empty($investorData['emergency_contact'])) {
        $emergencyContactData = [
            'person' => $investorData['emergency_contact']['person'] ?? '',
            'relation' => $investorData['emergency_contact']['relation'] ?? '',
            'phone' => $investorData['emergency_contact']['phone'] ?? '',
            'address' => $investorData['emergency_contact']['address'] ?? []
        ];
    }
    
    // Prepare basic_info
    $basicInfo = [];
    if (!empty($investorData['date_of_birth'])) {
        $basicInfo['date_of_birth'] = $investorData['date_of_birth'];
    }
    if (!empty($investorData['blood_group'])) {
        $basicInfo['blood_group'] = $investorData['blood_group'];
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert investor data
        $stmt = $pdo->prepare("
            INSERT INTO investors (
                uuid,
                sys_id, 
                type,
                name, 
                phone, 
                email, 
                address,
                basic_info,
                percentage,
                date_of_investing,
                emergency_contact,
                image_name,
                status, 
                meta_data
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $imageNamesJson = !empty($uploadedFiles) ? json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt->execute([
            $uuid,
            $sys_id,
            $investorData['type'] ?? 'permanent',
            $fullName,
            json_encode($phoneData, JSON_UNESCAPED_UNICODE),
            json_encode($emailData, JSON_UNESCAPED_UNICODE),
            json_encode($addressData, JSON_UNESCAPED_UNICODE),
            json_encode($basicInfo, JSON_UNESCAPED_UNICODE),
            $percentage,
            $dateOfInvesting,
            json_encode($emergencyContactData, JSON_UNESCAPED_UNICODE),
            $imageNamesJson,
            $investorData['status'] ?? 'active',
            $metaDataJson
        ]);
        
        $investorId = $pdo->lastInsertId();
        
        // Check if email already exists in login table
        $stmt = $pdo->prepare("SELECT 1 FROM login WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        
        if ($stmt->fetchColumn()) {
            throw new Exception('Email already registered in the system');
        }
        
        // Hash password (using sys_id as default password)
        $hashedPassword = password_hash($sys_id, PASSWORD_DEFAULT);
        
        // Define branch_id (default to 1)
        $branch_id = 1;
        
        // Insert into login table for system access
        $stmt = $pdo->prepare("
            INSERT INTO login (brunch_id, name, email, password, phone, user_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $branch_id,
            $fullName,
            $email,
            $hashedPassword,
            $phone,
            $sys_id
        ]);
        
        $loginId = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        // Prepare success response
        $response = [
            'success' => true,
            'message' => 'Investor added and system account created successfully',
            'investor_id' => $investorId,
            'login_id' => $loginId,
            'investor_uuid' => $uuid,
            'sys_id' => $sys_id,
            'folder_name' => $investorFolderName,
            'files_uploaded' => count($uploadedFiles),
            'data' => [
                'full_name' => $fullName,
                'date_of_investing' => $dateOfInvesting,
                'system_credentials' => [
                    'email' => $email,
                    'default_password' => $sys_id,
                    'note' => 'Investor should change password on first login'
                ]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
}

// Handle JSON request (without file upload)
function handleJsonRequest() {
    global $pdo;
    
    // Get POST data
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    
    if (!$data) {
        sendError('Invalid JSON data received', 400);
        return;
    }
    
    // Validate required fields
    validateRequiredFields($data);
    
    // Use default user since no authentication needed
    $createdBy = 'system_admin';
    $createdById = 0;
    
    // Generate meta data
    $metaDataJson = buildMetaData(null, $createdBy);
    
    // Extract investor info with defaults
    $dateOfInvesting = $data['date_of_investing'] ?? date('Y-m-d');
    $percentage = $data['percentage'] ?? 0;
    $fullName = $data['full_name'] ?? '';
    $phone = $data['phone'] ?? '';
    $email = $data['email'] ?? '';
    
    // Generate UUID and investor ID
    $uuid = generateUUID();
    $sys_id = generateInvestorId($dateOfInvesting);
    
    // Create investor folder
    $cleanSysId = preg_replace('/\s+/u', '', $sys_id);
    $cleanFullName = preg_replace('/\s+/u', '', $fullName);
    $investorFolderName = $cleanSysId . '_' . $cleanFullName;
    
    makeDir('investors', $investorFolderName);
    
    // Prepare address data for JSON storage
    $addressData = [];
    if (!empty($data['address'])) {
        $addressData = [
            'address_line_1' => $data['address']['address_line_1'] ?? '',
            'address_line_2' => $data['address']['address_line_2'] ?? '',
            'city' => $data['address']['city'] ?? '',
            'state' => $data['address']['state'] ?? '',
            'zip_code' => $data['address']['zip_code'] ?? '',
            'country' => $data['address']['country'] ?? ''
        ];
    }
    
    // Prepare emergency contact data for JSON storage
    $emergencyContactData = [];
    if (!empty($data['emergency_contact'])) {
        $emergencyContactData = [
            'person' => $data['emergency_contact']['person'] ?? '',
            'relation' => $data['emergency_contact']['relation'] ?? '',
            'phone' => $data['emergency_contact']['phone'] ?? '',
            'address' => $data['emergency_contact']['address'] ?? []
        ];
    }
    
    // Prepare basic_info
    $basicInfo = [];
    if (!empty($data['date_of_birth'])) {
        $basicInfo['date_of_birth'] = $data['date_of_birth'];
    }
    if (!empty($data['blood_group'])) {
        $basicInfo['blood_group'] = $data['blood_group'];
    }
    
    // Handle uploaded files from base64 (if any)
    $uploadedFiles = [];
    if (!empty($data['uploaded_files']) && is_array($data['uploaded_files'])) {
        $uploadedFiles = saveBase64Files($data['uploaded_files'], $investorFolderName);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert investor data
        $stmt = $pdo->prepare("
            INSERT INTO investors (
                uuid,
                sys_id, 
                type,
                name, 
                phone, 
                email, 
                address,
                basic_info,
                percentage,
                date_of_investing,
                emergency_contact,
                image_name,
                status, 
                meta_data
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $imageNamesJson = !empty($uploadedFiles) ? json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt->execute([
            $uuid,
            $sys_id,
            $data['type'] ?? 'permanent',
            $fullName,
            json_encode($phoneData, JSON_UNESCAPED_UNICODE),
            json_encode($emailData, JSON_UNESCAPED_UNICODE),
            json_encode($addressData, JSON_UNESCAPED_UNICODE),
            json_encode($basicInfo, JSON_UNESCAPED_UNICODE),
            $percentage,
            $dateOfInvesting,
            json_encode($emergencyContactData, JSON_UNESCAPED_UNICODE),
            $imageNamesJson,
            $data['status'] ?? 'active',
            $metaDataJson
        ]);
        
        $investorId = $pdo->lastInsertId();
        
        // Check if email already exists in login table
        $stmt = $pdo->prepare("SELECT 1 FROM login WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        
        if ($stmt->fetchColumn()) {
            throw new Exception('Email already registered in the system');
        }
        
        // Hash password (using sys_id as default password)
        $hashedPassword = password_hash($sys_id, PASSWORD_DEFAULT);
        
        // Define branch_id (default to 1)
        $branch_id = 1;
        
        // Insert into login table for system access
        $stmt = $pdo->prepare("
            INSERT INTO login (brunch_id, name, email, password, phone, user_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $branch_id,
            $fullName,
            $email,
            $hashedPassword,
            $phone,
            $sys_id
        ]);
        
        $loginId = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        // Prepare success response
        $response = [
            'success' => true,
            'message' => 'Investor added and system account created successfully',
            'investor_id' => $investorId,
            'login_id' => $loginId,
            'investor_uuid' => $uuid,
            'sys_id' => $sys_id,
            'folder_name' => $investorFolderName,
            'files_uploaded' => count($uploadedFiles),
            'data' => [
                'full_name' => $fullName,
                'date_of_investing' => $dateOfInvesting,
                'system_credentials' => [
                    'email' => $email,
                    'default_password' => $sys_id,
                    'note' => 'Investor should change password on first login'
                ]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
}

// Function to save base64 files
function saveBase64Files($files, $investorFolderName) {
    $uploadedFiles = [];
    $basePath = "../../uploads/investors/";
    $investorFolderPath = $basePath . $investorFolderName;
    
    // Create directory if not exists
    if (!file_exists($investorFolderPath)) {
        if (!mkdir($investorFolderPath, 0777, true)) {
            throw new Exception('Failed to create investor directory');
        }
    }
    
    if (!is_array($files) || empty($files)) {
        return $uploadedFiles;
    }
    
    foreach ($files as $file) {
        if (!empty($file['base64']) && !empty($file['name'])) {
            $base64Data = $file['base64'];
            $fileName = $file['name'];
            $fileType = $file['type'] ?? 'application/octet-stream';
            
            // Remove data URL prefix
            if (strpos($base64Data, 'base64,') !== false) {
                $base64Data = explode('base64,', $base64Data)[1];
            }
            
            // Decode base64
            $fileData = base64_decode($base64Data);
            
            if ($fileData !== false) {
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = uniqid() . '_' . date('Ymd_His') . '.' . $fileExtension;
                
                // Save file
                $destination = $investorFolderPath . '/' . $uniqueFileName;
                
                if (file_put_contents($destination, $fileData)) {
                    $uploadedFiles[] = [
                        'original_name' => $fileName,
                        'stored_name' => $uniqueFileName,
                        'file_type' => $fileType,
                        'file_size' => strlen($fileData),
                        'file_path' => "investors/{$investorFolderName}/{$uniqueFileName}",
                        'upload_date' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }
    }
    
    return $uploadedFiles;
}

// Function to validate required fields
function validateRequiredFields($data) {
    $requiredFields = ['full_name', 'phone', 'email'];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    // Validate phone structure
    if (!isset($data['phone'])) {
        throw new Exception('Primary phone number is required');
    }
    
    // Validate email structure
    if (!isset($data['email']['primary'])) {
        throw new Exception('Primary email is required');
    }
    
    // Add validation for date_of_investing
    if (empty($data['date_of_investing'])) {
        throw new Exception('Date of investing is required');
    }
    
    // Validate percentage (allow 0 but must be set)
    if (!isset($data['percentage'])) {
        throw new Exception('Percentage is required');
    }
}

// Function to send error response
function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Handle the request
handleRequest();