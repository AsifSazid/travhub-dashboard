<?php
// api/employees/get-all.php
// Returns active (or filtered) employees — sys_id, name, designation, department
// Used by HR pages (attendance, leave) to populate dropdowns.
// Requires hrm_employee_view permission.

session_start();
header('Content-Type: application/json');
require '../../server/db_connection.php';
require_once '../../server/permissions.php';

if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthenticated']);
    exit;
}

$isHR  = canAccess($pdo, 'hrm_employee_view') || ($_SESSION['user_role'] ?? '') === '0';
if (!$isHR) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Permission denied.']);
    exit;
}

$status = trim($_GET['status'] ?? 'active');
$allowed = ['active','inactive','all'];
if (!in_array($status, $allowed)) $status = 'active';

try {
    $where  = $status === 'all' ? '' : "WHERE e.status = '$status'";
    $stmt   = $pdo->query(
        "SELECT e.sys_id,
                e.name,
                e.department_name,
                JSON_UNQUOTE(JSON_EXTRACT(e.company_related_info,'$.designation')) AS designation,
                e.status,
                e.profile_photo
         FROM employees e
         $where
         ORDER BY e.name"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true, 'employees'=>$rows]);
} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$ex->getMessage()]);
}