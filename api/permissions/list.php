<?php
// PATH: /api/permissions/list.php
//
// Feeds pages/manage-permissions.php: every employee with the list of
// accounting permissions currently granted to them, plus the permission
// catalog itself (so the page renders whatever server/permissions.php
// defines and never has its own copy of the list to keep in sync).
//
// Department is returned only so the page can group and filter employees;
// it is never used to decide access.

session_start();
require '../../server/db_connection.php';
require_once '../../server/permissions.php';
header('Content-Type: application/json');

// (string) cast: role can arrive as int 0 or string '0' depending on the mysqli setup.
if ((string)($_SESSION['role'] ?? '') !== '0') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only a super-admin can view this list']);
    exit;
}

try {
    $employees = $pdo->query("
        SELECT sys_id, name, department_id, department_name
        FROM employees
        ORDER BY department_name ASC, name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $grantRows = $pdo->query("
        SELECT employee_sys_id, permission_key
        FROM employee_permissions
        WHERE revoked_at IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);

    $keysByEmployee = [];
    foreach ($grantRows as $r) {
        $keysByEmployee[$r['employee_sys_id']][] = $r['permission_key'];
    }

    $validKeys   = allAccountingPermissionKeys();
    $masterCount = 0;

    foreach ($employees as &$e) {
        $keys = $keysByEmployee[$e['sys_id']] ?? [];
        $e['permissions']   = array_values($keys);
        $e['has_master']    = in_array('full_accounting_access', $keys, true);
        $e['granted_count'] = count(array_intersect($keys, $validKeys));
        if ($e['has_master']) $masterCount++;
    }
    unset($e);

    $byDepartment = [];
    foreach ($employees as $e) {
        $byDepartment[$e['department_name'] ?: 'No Department'][] = $e;
    }

    echo json_encode([
        'success'       => true,
        'catalog'       => accountingPermissionCatalog(),
        'total_keys'    => count($validKeys),
        'employees'     => $employees,
        'by_department' => $byDepartment,
        'total_count'   => count($employees),
        'master_count'  => $masterCount,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}