<?php
// PATH: /api/permissions/list.php
//
// Lists all employees alongside their current grant status for a given
// permission_key, joined against their department (as a helpful default/
// filter in the UI, per the user's department-based starting point) --
// but the actual access decision always comes from employee_permissions,
// never inferred from department.
//
// GET ?permission_key=full_accounting_access

session_start();
require '../../server/db_connection.php';
header('Content-Type: application/json');

if (($_SESSION['role'] ?? null) !== '0') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only a super-admin can view this list']);
    exit;
}

$permissionKey = $_GET['permission_key'] ?? 'full_accounting_access';

try {
    $stmt = $pdo->prepare("
        SELECT
            e.sys_id, e.name, e.department_id, e.department_name,
            ep.granted_at, ep.granted_by,
            (ep.sys_id IS NOT NULL AND ep.revoked_at IS NULL) AS has_access
        FROM employees e
        LEFT JOIN employee_permissions ep
            ON ep.employee_sys_id = e.sys_id
            AND ep.permission_key = :pk
            AND ep.revoked_at IS NULL
        ORDER BY e.department_name ASC, e.name ASC
    ");
    $stmt->execute([':pk' => $permissionKey]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['has_access'] = (bool)$r['has_access'];
    }

    // Group by department for the UI's collapsible sections
    $byDept = [];
    foreach ($rows as $r) {
        $dept = $r['department_name'] ?: 'No Department';
        $byDept[$dept][] = $r;
    }

    echo json_encode([
        'success'        => true,
        'permission_key' => $permissionKey,
        'employees'      => $rows,
        'by_department'  => $byDept,
        'granted_count'  => count(array_filter($rows, fn($r) => $r['has_access'])),
        'total_count'    => count($rows),
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}