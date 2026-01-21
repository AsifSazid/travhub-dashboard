<?php
require '../../server/db_connection.php';
require '../../server/uuid_generator.php';
require '../../server/employee_id_generator.php';
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
    
    // Get employee data from POST
    $employeeData = json_decode($_POST['employee_data'], true);
    
    if (!$employeeData) {
        sendError('No employee data received', 400);
        return;
    }
    
    // Validate required fields
    validateRequiredFields($employeeData);
    
    // Use default user since no authentication needed
    $createdBy = 'system_admin';
    $createdById = 0;
    
    // Generate meta data
    $metaDataJson = buildMetaData(null, $createdBy);
    
    // Extract department info
    $department = $employeeData['department'] ?? null;
    $department_id = $employeeData['department_id'] ?? null;
    $dateOfJoin = $employeeData['company_related_info']['date_of_join'];
    $fullName = $employeeData['full_name'];
    $phone = $employeeData['phone']['primary_no'];
    $email = $employeeData['email']['primary'];
    
    // Generate UUID and employee ID
    $uuid = generateUUID();
    $sys_id = generateEmployeeId($department_id, $dateOfJoin);
    
    // Create employee folder
    $cleanSysId = preg_replace('/\s+/u', '', $sys_id);
    $cleanFullName = preg_replace('/\s+/u', '', $fullName);
    $employeeFolderName = $cleanSysId . '_' . $cleanFullName;
    
    // Create directory structure
    $basePath = "../../uploads/employees/";
    $employeeFolderPath = $basePath . $employeeFolderName;
    
    makeDir('employees', $employeeFolderName);
    
    // Handle file uploads
    $uploadedFiles = [];
    $files = $_FILES['files'];
    
    if (isset($files['name'][0]) && !empty($files['name'][0])) {
        // Ensure employee directory exists
        if (!file_exists($employeeFolderPath)) {
            mkdir($employeeFolderPath, 0777, true);
        }
        
        // Allowed file types
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];
        
        // Maximum file size (5MB)
        $maxFileSize = 5 * 1024 * 1024;
        
        // Process each file
        for ($i = 0; $i < count($files['name']); $i++) {
            $fileName = $files['name'][$i];
            $fileTmp = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileType = $files['type'][$i];
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
            
            // Generate unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueFileName = uniqid() . '_' . date('Ymd_His') . '.' . $fileExtension;
            
            // Destination path
            $destination = $employeeFolderPath . '/' . $uniqueFileName;
            
            // Move uploaded file
            if (move_uploaded_file($fileTmp, $destination)) {
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'stored_name' => $uniqueFileName,
                    'file_type' => $fileType,
                    'file_size' => $fileSize,
                    'file_path' => "employees/{$employeeFolderName}/{$uniqueFileName}",
                    'upload_date' => date('Y-m-d H:i:s')
                ];
            }
        }
    }
    
    // Prepare company_related_info JSON
    $companyRelatedInfo = [
        'designation' => $employeeData['company_related_info']['designation'],
        'company_role' => $employeeData['company_related_info']['company_role'],
        'date_of_join' => $dateOfJoin,
        'employment_type' => $employeeData['type'] ?? 'permanent',
        'status' => $employeeData['status'] ?? 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $createdBy,
        'created_by_id' => $createdById
    ];
    
    // Add department to company_related_info if available
    if ($department) {
        $companyRelatedInfo['department'] = $department;
        if ($department_id) {
            $companyRelatedInfo['department_id'] = $department_id;
        }
    }
    
    // Prepare phone data for JSON storage
    $phoneData = [
        'primary_no' => $phone
    ];
    
    // Add secondary phones if exists
    if (!empty($employeeData['phone']['secondary_no'])) {
        $phoneData['secondary_no'] = $employeeData['phone']['secondary_no'];
    }
    
    // Prepare email data for JSON storage
    $emailData = [
        'primary' => $email
    ];
    
    // Add secondary emails if exists
    if (!empty($employeeData['email']['secondary'])) {
        $emailData['secondary'] = $employeeData['email']['secondary'];
    }
    
    // Prepare address data for JSON storage
    $addressData = [];
    if (!empty($employeeData['address'])) {
        $addressData = [
            'address_line_1' => $employeeData['address']['address_line_1'] ?? '',
            'address_line_2' => $employeeData['address']['address_line_2'] ?? '',
            'city' => $employeeData['address']['city'] ?? '',
            'state' => $employeeData['address']['state'] ?? '',
            'zip_code' => $employeeData['address']['zip_code'] ?? '',
            'country' => $employeeData['address']['country'] ?? ''
        ];
    }
    
    // Prepare emergency contact data for JSON storage
    $emergencyContactData = [];
    if (!empty($employeeData['emergency_contact'])) {
        $emergencyContactData = [
            'person' => $employeeData['emergency_contact']['person'] ?? '',
            'relation' => $employeeData['emergency_contact']['relation'] ?? '',
            'phone' => $employeeData['emergency_contact']['phone'] ?? '',
            'address' => $employeeData['emergency_contact']['address'] ?? []
        ];
    }
    
    // Prepare basic_info
    $basicInfo = [];
    if (!empty($employeeData['date_of_birth'])) {
        $basicInfo['date_of_birth'] = $employeeData['date_of_birth'];
    }
    if (!empty($employeeData['blood_group'])) {
        $basicInfo['blood_group'] = $employeeData['blood_group'];
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert employee data
        $stmt = $pdo->prepare("
            INSERT INTO employees (
                uuid,
                sys_id, 
                type, 
                department_id,
                department_name,
                name, 
                phone, 
                email, 
                address,
                basic_info,
                company_related_info,
                emergency_contact,
                image_name,
                status, 
                meta_data
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $imageNamesJson = !empty($uploadedFiles) ? json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt->execute([
            $uuid,
            $sys_id,
            $employeeData['type'] ?? 'permanent',
            $department_id,
            $department,
            $fullName,
            json_encode($phoneData, JSON_UNESCAPED_UNICODE),
            json_encode($emailData, JSON_UNESCAPED_UNICODE),
            json_encode($addressData, JSON_UNESCAPED_UNICODE),
            json_encode($basicInfo, JSON_UNESCAPED_UNICODE),
            json_encode($companyRelatedInfo, JSON_UNESCAPED_UNICODE),
            json_encode($emergencyContactData, JSON_UNESCAPED_UNICODE),
            $imageNamesJson,
            $employeeData['status'] ?? 'active',
            $metaDataJson
        ]);
        
        $employeeId = $pdo->lastInsertId();
        
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
            INSERT INTO login (brunch_id, name, email, password, phone, employee_id)
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
            'message' => 'Employee added and system account created successfully',
            'employee_id' => $employeeId,
            'login_id' => $loginId,
            'employee_uuid' => $uuid,
            'sys_id' => $sys_id,
            'folder_name' => $employeeFolderName,
            'files_uploaded' => count($uploadedFiles),
            'data' => [
                'full_name' => $fullName,
                'designation' => $employeeData['company_related_info']['designation'],
                'department' => $department,
                'date_of_join' => $dateOfJoin,
                'system_credentials' => [
                    'email' => $email,
                    'default_password' => $sys_id,
                    'note' => 'Employee should change password on first login'
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
    
    // Extract department info
    $department = $data['department'] ?? null;
    $department_id = $data['department_id'] ?? null;
    $dateOfJoin = $data['company_related_info']['date_of_join'];
    $fullName = $data['full_name'];
    $phone = $data['phone']['primary_no'];
    $email = $data['email']['primary'];
    
    // Generate UUID and employee ID
    $uuid = generateUUID();
    $sys_id = generateEmployeeId($department_id, $dateOfJoin);
    
    // Create employee folder
    $cleanSysId = preg_replace('/\s+/u', '', $sys_id);
    $cleanFullName = preg_replace('/\s+/u', '', $fullName);
    $employeeFolderName = $cleanSysId . '_' . $cleanFullName;
    
    makeDir('employees', $employeeFolderName);
    
    // Prepare company_related_info JSON
    $companyRelatedInfo = [
        'designation' => $data['company_related_info']['designation'],
        'company_role' => $data['company_related_info']['company_role'],
        'date_of_join' => $dateOfJoin,
        'employment_type' => $data['type'] ?? 'permanent',
        'status' => $data['status'] ?? 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $createdBy,
        'created_by_id' => $createdById
    ];
    
    // Add department to company_related_info if available
    if ($department) {
        $companyRelatedInfo['department'] = $department;
        if ($department_id) {
            $companyRelatedInfo['department_id'] = $department_id;
        }
    }
    
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
        $uploadedFiles = saveBase64Files($data['uploaded_files'], $employeeFolderName);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert employee data
        $stmt = $pdo->prepare("
            INSERT INTO employees (
                uuid,
                sys_id, 
                type, 
                department_id,
                department_name,
                name, 
                phone, 
                email, 
                address,
                basic_info,
                company_related_info,
                emergency_contact,
                image_name,
                status, 
                meta_data
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $imageNamesJson = !empty($uploadedFiles) ? json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt->execute([
            $uuid,
            $sys_id,
            $data['type'] ?? 'permanent',
            $department_id,
            $department,
            $fullName,
            json_encode($phoneData, JSON_UNESCAPED_UNICODE),
            json_encode($emailData, JSON_UNESCAPED_UNICODE),
            json_encode($addressData, JSON_UNESCAPED_UNICODE),
            json_encode($basicInfo, JSON_UNESCAPED_UNICODE),
            json_encode($companyRelatedInfo, JSON_UNESCAPED_UNICODE),
            json_encode($emergencyContactData, JSON_UNESCAPED_UNICODE),
            $imageNamesJson,
            $data['status'] ?? 'active',
            $metaDataJson
        ]);
        
        $employeeId = $pdo->lastInsertId();
        
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
            INSERT INTO login (brunch_id, name, email, password, phone, employee_id)
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
            'message' => 'Employee added and system account created successfully',
            'employee_id' => $employeeId,
            'login_id' => $loginId,
            'employee_uuid' => $uuid,
            'sys_id' => $sys_id,
            'folder_name' => $employeeFolderName,
            'files_uploaded' => count($uploadedFiles),
            'data' => [
                'full_name' => $fullName,
                'designation' => $data['company_related_info']['designation'],
                'department' => $department,
                'date_of_join' => $dateOfJoin,
                'system_credentials' => [
                    'email' => $email,
                    'default_password' => $sys_id,
                    'note' => 'Employee should change password on first login'
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
function saveBase64Files($files, $employeeFolderName) {
    $uploadedFiles = [];
    $basePath = "../../uploads/employees/";
    $employeeFolderPath = $basePath . $employeeFolderName;
    
    // Create directory if not exists
    if (!file_exists($employeeFolderPath)) {
        mkdir($employeeFolderPath, 0777, true);
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
                $destination = $employeeFolderPath . '/' . $uniqueFileName;
                
                if (file_put_contents($destination, $fileData)) {
                    $uploadedFiles[] = [
                        'original_name' => $fileName,
                        'stored_name' => $uniqueFileName,
                        'file_type' => $fileType,
                        'file_size' => strlen($fileData),
                        'file_path' => "employees/{$employeeFolderName}/{$uniqueFileName}",
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
    $requiredFields = ['full_name', 'phone', 'email', 'company_related_info'];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }
    
    // Validate specific company_related_info fields
    if (empty($data['company_related_info']['designation']) || 
        empty($data['company_related_info']['company_role']) || 
        empty($data['company_related_info']['date_of_join'])) {
        throw new Exception('Designation, company role, and date of join are required');
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