<?php
// PATH: /api/permissions/toggle.php
//
// Grants or revokes a permission for one employee. Only a super-admin
// (login.role='0') may call this -- granting/revoking access is itself an
// accounting-adjacent admin action, so it uses the same super-admin
// convention as the rest of this codebase rather than requiring the
// permission being granted (that would let an accounting-access holder
// grant it to others, which the user hasn't asked for).
//
// POST { employee_sys_id, permission_key, grant: true|false }

session_start();

require '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (($_SESSION['role'] ?? null) !== '0') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only a super-admin can grant or revoke permissions']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $employeeSysId  = trim($input['employee_sys_id'] ?? '');
    $permissionKey  = trim($input['permission_key'] ?? '');
    $grant          = !empty($input['grant']);

    if (!$employeeSysId || !$permissionKey) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'employee_sys_id and permission_key are required']);
        exit;
    }

    $adminName = $_SESSION['user_name'] ?? 'system';

    if ($grant) {
        // Idempotent: if an active grant already exists, do nothing; if a
        // previously-revoked row exists, re-activate it rather than
        // inserting a duplicate.
        $existing = $pdo->prepare("SELECT sys_id, revoked_at FROM employee_permissions WHERE employee_sys_id = ? AND permission_key = ?");
        $existing->execute([$employeeSysId, $permissionKey]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['revoked_at'] === null) {
            echo json_encode(['success' => true, 'message' => 'Already granted']);
            exit;
        }

        if ($row) {
            $pdo->prepare("UPDATE employee_permissions SET revoked_at = NULL, granted_by = ?, granted_at = NOW() WHERE sys_id = ?")
                ->execute([$adminName, $row['sys_id']]);
        } else {
            $ids = generateV2IDs($pdo, 'employee_permissions');
            $meta = buildMetaData(null, $adminName);
            $pdo->prepare("
                INSERT INTO employee_permissions (uuid, sys_id, employee_sys_id, permission_key, granted_by, granted_at, meta_data)
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ")->execute([$ids['uuid'], $ids['sys_id'], $employeeSysId, $permissionKey, $adminName, $meta]);
        }

        echo json_encode(['success' => true, 'message' => 'Permission granted']);
    } else {
        $pdo->prepare("UPDATE employee_permissions SET revoked_at = NOW() WHERE employee_sys_id = ? AND permission_key = ? AND revoked_at IS NULL")
            ->execute([$employeeSysId, $permissionKey]);
        echo json_encode(['success' => true, 'message' => 'Permission revoked']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}