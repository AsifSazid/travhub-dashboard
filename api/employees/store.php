<?php
require '../../server/db_connection.php';
require '../../server/uuid_generator.php';
require '../../server/employee_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
$requiredFields = ['full_name', 'phone', 'email', 'company_related_info'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit;
    }
}

// Validate specific company_related_info fields
if (empty($data['company_related_info']['designation']) || 
    empty($data['company_related_info']['company_role']) || 
    empty($data['company_related_info']['date_of_join'])) {
    echo json_encode(['success' => false, 'message' => 'Designation, company role, and date of join are required']);
    exit;
}

// Validate phone structure
if (!isset($data['phone']['primary_no'])) {
    echo json_encode(['success' => false, 'message' => 'Primary phone number is required']);
    exit;
}

// Validate email structure
if (!isset($data['email']['primary'])) {
    echo json_encode(['success' => false, 'message' => 'Primary email is required']);
    exit;
}

try {
    // Generate UUID and employee ID
    $uuid = generateUUID();
    $sys_id = generateEmployeeId();
    
    // Get current user from session
    $createdBy = $_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'system');
    
    // Generate meta data
    $metaDataJson = buildMetaData(
        null,
        $createdBy
    );
    
    // Extract department info from department input
    $department = null;
    $department_id = null;
    
    if (!empty($data['department'])) {
        // Check if department input contains ID and name format (e.g., "1 | Management")
        if (strpos($data['department'], '|') !== false) {
            $parts = explode('|', $data['department']);
            if (count($parts) >= 2) {
                $department_id = trim($parts[0]);
                $department = trim($parts[1]);
            }
        } else {
            $department = $data['department'];
        }
    }
    
    // Create employee folder
    $cleanSysId = preg_replace('/\s+/u', '', $sys_id);
    $cleanFullName = preg_replace('/\s+/u', '', $data['full_name']);
    $employeeFolderName = $cleanSysId . '_' . $cleanFullName;
    
    makeDir('employees', $employeeFolderName);
    
    // Prepare company_related_info JSON
    $companyRelatedInfo = [
        'designation' => $data['company_related_info']['designation'],
        'company_role' => $data['company_related_info']['company_role'],
        'date_of_join' => $data['company_related_info']['date_of_join'],
        'employment_type' => $data['type'] ?? 'permanent',
        'status' => $data['status'] ?? 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $createdBy
    ];
    
    // Add department to company_related_info if available
    if ($department) {
        $companyRelatedInfo['department'] = $department;
        if ($department_id) {
            $companyRelatedInfo['department_id'] = $department_id;
        }
    }
    
    // Add date of birth if provided
    if (!empty($data['date_of_birth'])) {
        $companyRelatedInfo['date_of_birth'] = $data['date_of_birth'];
    }
    
    // Prepare phone data for JSON storage
    $phoneData = [
        'primary_no' => $data['phone']['primary_no']
    ];
    
    // Add secondary phones if exists
    if (!empty($data['phone']['secondary_no'])) {
        $phoneData['secondary_no'] = $data['phone']['secondary_no'];
    }
    
    // Prepare email data for JSON storage
    $emailData = [
        'primary' => $data['email']['primary']
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
    
    // Prepare basic_info (can include date of birth)
    $basicInfo = [];
    if (!empty($data['date_of_birth'])) {
        $basicInfo['date_of_birth'] = $data['date_of_birth'];
    }
    
    // Prepare SQL
    $stmt = $pdo->prepare("
        INSERT INTO employees (
            uuid,
            sys_id, 
            type, 
            department_id,
            name, 
            phone, 
            email, 
            address,
            basic_info,
            company_related_info,
            status, 
            meta_data,
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Execute
    $stmt->execute([
        $uuid,
        $sys_id,
        $data['type'] ?? 'permanent',
        $department_id,
        $data['full_name'],
        json_encode($phoneData, JSON_UNESCAPED_UNICODE),
        json_encode($emailData, JSON_UNESCAPED_UNICODE),
        json_encode($addressData, JSON_UNESCAPED_UNICODE),
        json_encode($basicInfo, JSON_UNESCAPED_UNICODE),
        json_encode($companyRelatedInfo, JSON_UNESCAPED_UNICODE),
        $data['status'] ?? 'active',
        $metaDataJson
    ]);

    $employeeId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Employee added successfully',
        'employee_id' => $employeeId,
        'employee_uuid' => $uuid,
        'sys_id' => $sys_id,
        'data' => [
            'full_name' => $data['full_name'],
            'designation' => $data['company_related_info']['designation'],
            'department' => $department,
            'date_of_join' => $data['company_related_info']['date_of_join']
        ]
    ]);
} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}