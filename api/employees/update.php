<?php
// PATH: /api/employees/update.php
//
// Updates an existing employee. Did not exist before -- store.php only ever
// INSERTs (once for multipart/form-data requests, once for JSON requests),
// so there was previously no way to change an employee's details after
// creation at all.
//
// Accepts the same JSON shape create-employee.php's JS builds (full_name,
// company_related_info, phone, email, address, emergency_contact,
// date_of_birth, blood_group), plus sys_id to identify which employee to
// update. Fields not sent are left unchanged -- this merges into the
// existing JSON blobs rather than requiring the whole form to be resent.

session_start();
require '../../server/db_connection.php';
require_once '../../server/hrm_permissions.php';
requireHrm($pdo, 'hrm_employee_edit');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }

    $sysId = trim($data['sys_id'] ?? '');
    if (!$sysId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'sys_id is required']);
        exit;
    }

    /* ================= Load the existing row, so unspecified fields merge instead of being wiped ================= */
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

    $existingCompanyInfo = json_decode($existing['company_related_info'] ?? '{}', true) ?: [];
    $existingBasicInfo    = json_decode($existing['basic_info'] ?? '{}', true) ?: [];
    $existingPhone        = json_decode($existing['phone'] ?? '{}', true) ?: [];
    $existingEmail        = json_decode($existing['email'] ?? '{}', true) ?: [];
    $existingAddress      = json_decode($existing['address'] ?? '{}', true) ?: [];
    $existingEmergency    = json_decode($existing['emergency_contact'] ?? '{}', true) ?: [];

    /* ================= Merge: only overwrite keys that were actually sent ================= */
    $fullName      = $data['full_name'] ?? $existing['name'];
    $department    = $data['department'] ?? ($existingCompanyInfo['department'] ?? $existing['department_name']);
    $departmentId  = $data['department_id'] ?? ($existingCompanyInfo['department_id'] ?? $existing['department_id']);

    $companyRelatedInfo = array_merge($existingCompanyInfo, array_filter([
        'designation'              => $data['company_related_info']['designation'] ?? null,
        'company_role'             => $data['company_related_info']['company_role'] ?? null,
        'date_of_join'             => $data['company_related_info']['date_of_join'] ?? null,
        'father_name'              => $data['company_related_info']['father_name'] ?? null,
        'mother_name'              => $data['company_related_info']['mother_name'] ?? null,
        'spouse_name'              => $data['company_related_info']['spouse_name'] ?? null,
        'nid_no'                   => $data['company_related_info']['nid_no'] ?? null,
        'gross_salary'             => $data['company_related_info']['gross_salary'] ?? null,
        'reporting_to_name'        => $data['company_related_info']['reporting_to_name'] ?? null,
        'reporting_to_designation' => $data['company_related_info']['reporting_to_designation'] ?? null,
    ], fn($v) => $v !== null));
    if ($department) {
        $companyRelatedInfo['department'] = $department;
        if ($departmentId) $companyRelatedInfo['department_id'] = $departmentId;
    }
    $companyRelatedInfo['updated_at'] = date('Y-m-d H:i:s');
    $companyRelatedInfo['updated_by'] = $_SESSION['user_name'] ?? 'system';

    $basicInfo = array_merge($existingBasicInfo, array_filter([
        'date_of_birth' => $data['date_of_birth'] ?? null,
        'blood_group'   => $data['blood_group'] ?? null,
    ], fn($v) => $v !== null));

    $phoneData = array_merge($existingPhone, array_filter([
        'primary_no'   => $data['phone']['primary_no'] ?? null,
        'secondary_no' => $data['phone']['secondary_no'] ?? null,
    ], fn($v) => $v !== null));

    $emailData = array_merge($existingEmail, array_filter([
        'primary'   => $data['email']['primary'] ?? null,
        'secondary' => $data['email']['secondary'] ?? null,
    ], fn($v) => $v !== null));

    $addressData = !empty($data['address']) ? array_merge($existingAddress, array_filter([
        'address_line_1' => $data['address']['address_line_1'] ?? null,
        'address_line_2' => $data['address']['address_line_2'] ?? null,
        'city'           => $data['address']['city'] ?? null,
        'state'          => $data['address']['state'] ?? null,
        'zip_code'       => $data['address']['zip_code'] ?? null,
        'country'        => $data['address']['country'] ?? null,
    ], fn($v) => $v !== null)) : $existingAddress;

    $emergencyContactData = !empty($data['emergency_contact']) ? array_merge($existingEmergency, array_filter([
        'person'   => $data['emergency_contact']['person'] ?? null,
        'relation' => $data['emergency_contact']['relation'] ?? null,
        'phone'    => $data['emergency_contact']['phone'] ?? null,
        'address'  => $data['emergency_contact']['address'] ?? null,
    ], fn($v) => $v !== null)) : $existingEmergency;

    $status = $data['status'] ?? $existing['status'];
    $type   = $data['type'] ?? $existing['type'];

    /* ================= Update ================= */
    $pdo->prepare("
        UPDATE employees SET
            name = ?, type = ?, department_id = ?, department_name = ?,
            phone = ?, email = ?, address = ?, basic_info = ?,
            company_related_info = ?, emergency_contact = ?, status = ?
        WHERE sys_id = ?
    ")->execute([
        $fullName, $type, $departmentId, $department,
        json_encode($phoneData, JSON_UNESCAPED_UNICODE),
        json_encode($emailData, JSON_UNESCAPED_UNICODE),
        json_encode($addressData, JSON_UNESCAPED_UNICODE),
        json_encode($basicInfo, JSON_UNESCAPED_UNICODE),
        json_encode($companyRelatedInfo, JSON_UNESCAPED_UNICODE),
        json_encode($emergencyContactData, JSON_UNESCAPED_UNICODE),
        $status,
        $sysId,
    ]);

    echo json_encode(['success' => true, 'message' => 'Employee updated']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}