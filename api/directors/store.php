<?php
// api/directors/store.php
session_start();
require '../../server/db_connection.php';
require '../../server/make-smb-dir.php';
require_once '../../server/live_storage.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/director_id_generator.php';
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
    
    // Get director data from POST
    $directorData = json_decode($_POST['director_data'], true);
    
    if (!$directorData) {
        sendError('No director data received', 400);
        return;
    }
    
    // Validate required fields
    validateRequiredFields($directorData);
    
    // Use default user since no authentication needed
    $createdBy = $_SESSION['user_name'] ?? 'system';
    $createdById = 0;
    
    // Generate meta data
    $metaDataJson = buildMetaData(null, $createdBy);
    
    $fullName = $directorData['full_name'];
    $phone = $directorData['phone']['primary_no'];
    $email = $directorData['email']['primary'];
    
    // Generate UUID and director ID
    $uuid = generateUUID();
    $sys_id = generateDirectorId();
    
    // Create director folder
    $cleanSysId = preg_replace('/\s+/u', '', $sys_id);
    $cleanFullName = preg_replace('/\s+/u', '', $fullName);
    $directorFolderName = $cleanSysId . '_' . $cleanFullName;
    
    // Create local directory (kept for legacy serve compatibility)
    $basePath = "../../uploads/directors/";
    $directorFolderPath = $basePath . $directorFolderName;
    makeDir('directors', $directorFolderName);

    // SMB path: dev_hr/{sysId}_{Name}/
    $SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt'));
    $smbHrBase       = "{$SERVER_CUS_PATH}_hr";
    $cloudFolderPath = makeSMBDir($smbHrBase, $directorFolderName);
    $cloudFolderPath = rtrim($cloudFolderPath, '/');
    $cloudOk         = !str_starts_with($cloudFolderPath, '❌');
    if (!$cloudOk) {
        error_log("SMB HR folder creation failed for director $sys_id: $cloudFolderPath");
    }

    // Handle file uploads
    $uploadedFiles = [];
    $files = $_FILES['files'];

    if (isset($files['name'][0]) && !empty($files['name'][0])) {

        if (!file_exists($directorFolderPath)) {
            mkdir($directorFolderPath, 0777, true);
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];

        $maxFileSize = 5 * 1024 * 1024;

        for ($i = 0; $i < count($files['name']); $i++) {

            $fileName  = $files['name'][$i];
            $fileTmp   = $files['tmp_name'][$i];
            $fileSize  = $files['size'][$i];
            $fileType  = $files['type'][$i];
            $fileError = $files['error'][$i];

            if ($fileError !== UPLOAD_ERR_OK) continue;
            if (!isset($allowedTypes[$fileType])) continue;
            if ($fileSize > $maxFileSize) continue;

            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $baseName      = ($i === 0) ? 'profile-pic' : 'image-' . $i;

            $counter     = 1;
            $finalName   = $baseName . '.' . $fileExtension;
            $destination = $directorFolderPath . '/' . $finalName;
            while (file_exists($destination)) {
                $counter++;
                $finalName   = $baseName . '-' . $counter . '.' . $fileExtension;
                $destination = $directorFolderPath . '/' . $finalName;
            }

            if (move_uploaded_file($fileTmp, $destination)) {
                // Mirror to SMB
                if ($cloudOk) {
                    $omv    = new OMV_SMB_Manager();
                    $dest   = $cloudFolderPath . '/' . $finalName;
                    $status = $omv->paste_file($destination, $dest);
                    if ($status !== true) {
                        error_log("SMB director file upload failed: $dest :: $status");
                    }
                }
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'stored_name'   => $finalName,
                    'file_type'     => $fileType,
                    'file_size'     => $fileSize,
                    'file_path'     => "directors/{$directorFolderName}/{$finalName}",
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
    if (!empty($directorData['phone']['secondary_no'])) {
        $phoneData['secondary_no'] = $directorData['phone']['secondary_no'];
    }
    
    // Prepare email data for JSON storage
    $emailData = [
        'primary' => $email
    ];
    
    // Add secondary emails if exists
    if (!empty($directorData['email']['secondary'])) {
        $emailData['secondary'] = $directorData['email']['secondary'];
    }
    
    // Prepare address data for JSON storage
    $addressData = [];
    if (!empty($directorData['address'])) {
        $addressData = [
            'address_line_1' => $directorData['address']['address_line_1'] ?? '',
            'address_line_2' => $directorData['address']['address_line_2'] ?? '',
            'city' => $directorData['address']['city'] ?? '',
            'state' => $directorData['address']['state'] ?? '',
            'zip_code' => $directorData['address']['zip_code'] ?? '',
            'country' => $directorData['address']['country'] ?? ''
        ];
    }
    
    // Prepare emergency contact data for JSON storage
    $emergencyContactData = [];
    if (!empty($directorData['emergency_contact'])) {
        $emergencyContactData = [
            'person' => $directorData['emergency_contact']['person'] ?? '',
            'relation' => $directorData['emergency_contact']['relation'] ?? '',
            'phone' => $directorData['emergency_contact']['phone'] ?? '',
            'address' => $directorData['emergency_contact']['address'] ?? []
        ];
    }
    
    // Prepare basic_info
    $basicInfo = [];
    if (!empty($directorData['date_of_birth'])) {
        $basicInfo['date_of_birth'] = $directorData['date_of_birth'];
    }
    if (!empty($directorData['blood_group'])) {
        $basicInfo['blood_group'] = $directorData['blood_group'];
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert director data
        $stmt = $pdo->prepare("
            INSERT INTO directors (
                uuid,
                sys_id,
                name, 
                phone, 
                email, 
                address,
                basic_info,
                emergency_contact,
                image_name,
                status, 
                meta_data
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $imageNamesJson = !empty($uploadedFiles) ? json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt->execute([
            $uuid,
            $sys_id,
            $fullName,
            json_encode($phoneData, JSON_UNESCAPED_UNICODE),
            json_encode($emailData, JSON_UNESCAPED_UNICODE),
            json_encode($addressData, JSON_UNESCAPED_UNICODE),
            json_encode($basicInfo, JSON_UNESCAPED_UNICODE),
            json_encode($emergencyContactData, JSON_UNESCAPED_UNICODE),
            $imageNamesJson,
            $directorData['status'] ?? 'active',
            $metaDataJson
        ]);
        
        $directorId = $pdo->lastInsertId();
        
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
            'message' => 'Director added and system account created successfully',
            'director_id' => $directorId,
            'login_id' => $loginId,
            'director_uuid' => $uuid,
            'sys_id' => $sys_id,
            'folder_name' => $directorFolderName,
            'files_uploaded' => count($uploadedFiles),
            'data' => [
                'full_name' => $fullName,
                'system_credentials' => [
                    'email' => $email,
                    'default_password' => $sys_id,
                    'note' => 'Director should change password on first login'
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
    
    $fullName = $data['full_name'];
    $phone = $data['phone']['primary_no'];
    $email = $data['email']['primary'];
    
    // Generate UUID and director ID
    $uuid = generateUUID();
    $sys_id = generateDirectorId();
    
    // Create director folder
    $cleanSysId = preg_replace('/\s+/u', '', $sys_id);
    $cleanFullName = preg_replace('/\s+/u', '', $fullName);
    $directorFolderName = $cleanSysId . '_' . $cleanFullName;
    
    makeDir('directors', $directorFolderName);
    
    // Prepare phone data for JSON storage
    $phoneData = [
        'primary_no' => $phone
    ];
    
    // Add secondary phones if exists
    if (!empty($data['phone']['secondary_no'])) {
        $phoneData['secondary_no'] = $data['phone']['secondary_no'];
    }
    
    // Prepare email data for JSON storage
    $emailData = [
        'primary' => $email
    ];
    
    // Add secondary emails if exists
    if (!empty($data['email']['secondary'])) {
        $emailData['secondary'] = $data['email']['secondary'];
    }
    
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
        $uploadedFiles = saveBase64Files($data['uploaded_files'], $directorFolderName);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert director data
        $stmt = $pdo->prepare("
            INSERT INTO directors (
                uuid,
                sys_id, 
                name, 
                phone, 
                email, 
                address,
                basic_info,
                emergency_contact,
                image_name,
                status, 
                meta_data
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $imageNamesJson = !empty($uploadedFiles) ? json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt->execute([
            $uuid,
            $sys_id,
            $fullName,
            json_encode($phoneData, JSON_UNESCAPED_UNICODE),
            json_encode($emailData, JSON_UNESCAPED_UNICODE),
            json_encode($addressData, JSON_UNESCAPED_UNICODE),
            json_encode($basicInfo, JSON_UNESCAPED_UNICODE),
            json_encode($emergencyContactData, JSON_UNESCAPED_UNICODE),
            $imageNamesJson,
            $data['status'] ?? 'active',
            $metaDataJson
        ]);
        
        $directorId = $pdo->lastInsertId();
        
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
            'message' => 'Director added and system account created successfully',
            'director_id' => $directorId,
            'login_id' => $loginId,
            'director_uuid' => $uuid,
            'sys_id' => $sys_id,
            'folder_name' => $directorFolderName,
            'files_uploaded' => count($uploadedFiles),
            'data' => [
                'full_name' => $fullName,
                'system_credentials' => [
                    'email' => $email,
                    'default_password' => $sys_id,
                    'note' => 'Director should change password on first login'
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
function saveBase64Files($files, $directorFolderName) {
    $uploadedFiles = [];
    $basePath = "../../uploads/directors/";
    $directorFolderPath = $basePath . $directorFolderName;
    
    // Create directory if not exists
    if (!file_exists($directorFolderPath)) {
        mkdir($directorFolderPath, 0777, true);
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
                $destination = $directorFolderPath . '/' . $uniqueFileName;
                
                if (file_put_contents($destination, $fileData)) {
                    $uploadedFiles[] = [
                        'original_name' => $fileName,
                        'stored_name' => $uniqueFileName,
                        'file_type' => $fileType,
                        'file_size' => strlen($fileData),
                        'file_path' => "directors/{$directorFolderName}/{$uniqueFileName}",
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
    if (!isset($data['phone']['primary_no'])) {
        throw new Exception('Primary phone number is required');
    }
    
    // Validate email structure
    if (!isset($data['email']['primary'])) {
        throw new Exception('Primary email is required');
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