<?php
// PATH: /api/permissions/toggle.php
//
// Grants or revokes accounting permissions for one employee. Super-admin
// only (login.role = '0'): being allowed to grant access must not itself be
// something an accounting-access holder can hand out to others.
//
// POST {
//   employee_sys_id,
//   grant: true|false,
//   permission_key:  'report_profit'                    -- one key, or
//   permission_keys: ['report_profit', 'entry_expense'] -- several at once
// }
// Only keys defined in server/permissions.php (plus the master
// 'full_accounting_access') are accepted, so a typo or a made-up key can
// never be written into the table.

session_start();

require '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require_once '../../server/permissions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Allow: super-admin (role '0') OR holder of full_accounting_access (master switch)
$actorRole    = (string)($_SESSION['role'] ?? '');
$actorId      = $_SESSION['user_id'] ?? '';
$isSuperAdmin = $actorRole === '0';
if (!$isSuperAdmin && !hasPermission($pdo, $actorId, 'full_accounting_access')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $employeeSysId = trim($input['employee_sys_id'] ?? '');
    $grant         = !empty($input['grant']);

    $keys = [];
    if (!empty($input['permission_keys']) && is_array($input['permission_keys'])) {
        $keys = $input['permission_keys'];
    } elseif (!empty($input['permission_key'])) {
        $keys = [$input['permission_key']];
    }
    $keys = array_values(array_unique(array_map('trim', $keys)));

    if (!$employeeSysId || !$keys) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'employee_sys_id and at least one permission key are required']);
        exit;
    }

    $allowed = array_merge(allAccountingPermissionKeys(), ['full_accounting_access']);
    $unknown = array_diff($keys, $allowed);
    if ($unknown) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown permission key(s): ' . implode(', ', $unknown)]);
        exit;
    }

    // Only super-admin may grant/revoke the master switch itself
    if (!$isSuperAdmin && in_array('full_accounting_access', $keys, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only a super-admin can modify the Full Access master switch']);
        exit;
    }

    $adminName = $_SESSION['user_name'] ?? 'system';

    $pdo->beginTransaction();

    foreach ($keys as $key) {
        if ($grant) {
            // Idempotent: already active -> nothing; previously revoked ->
            // reactivate that row rather than inserting a duplicate.
            $existing = $pdo->prepare("SELECT sys_id, revoked_at FROM employee_permissions WHERE employee_sys_id = ? AND permission_key = ?");
            $existing->execute([$employeeSysId, $key]);
            $row = $existing->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['revoked_at'] === null) continue;

            if ($row) {
                $pdo->prepare("UPDATE employee_permissions SET revoked_at = NULL, granted_by = ?, granted_at = NOW() WHERE sys_id = ?")
                    ->execute([$adminName, $row['sys_id']]);
            } else {
                $ids  = generateV2IDs($pdo, 'employee_permissions');
                $meta = buildMetaData(null, $adminName);
                $pdo->prepare("
                    INSERT INTO employee_permissions (uuid, sys_id, employee_sys_id, permission_key, granted_by, granted_at, meta_data)
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)
                ")->execute([$ids['uuid'], $ids['sys_id'], $employeeSysId, $key, $adminName, $meta]);
            }
        } else {
            $pdo->prepare("UPDATE employee_permissions SET revoked_at = NOW() WHERE employee_sys_id = ? AND permission_key = ? AND revoked_at IS NULL")
                ->execute([$employeeSysId, $key]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => ($grant ? 'Granted ' : 'Revoked ') . count($keys) . ' permission' . (count($keys) === 1 ? '' : 's'),
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}