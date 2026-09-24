<?php
// PATH: /server/permissions.php
//
// Granular, admin-controlled permission checks — separate from login.role
// (which is only 0=admin/1=everyone-else, too coarse for "who can see full
// accounting" per the user's explicit decision) and separate from
// designation/department (which are useful DEFAULTS but not the final
// word, since the user wants individual on/off control regardless of
// department or title).
//
// Backed by a new `employee_permissions` table: one row per
// (employee_sys_id, permission_key) grant. Absence of a row = no
// permission. This file is the single place that decides what a
// permission_key STRING means; callers just ask hasPermission().

/**
 * Checks whether the given employee has been explicitly granted a
 * permission. Always requires an explicit grant row -- there is
 * deliberately no department/designation fallback here, because the user
 * wants a real per-person on/off switch, not an inferred default that
 * silently changes when someone's department or title changes.
 */
function hasPermission(PDO $pdo, string $employeeSysId, string $permissionKey): bool
{
    if (!$employeeSysId) return false;

    $stmt = $pdo->prepare("
        SELECT 1 FROM employee_permissions
        WHERE employee_sys_id = ? AND permission_key = ? AND revoked_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$employeeSysId, $permissionKey]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Convenience wrapper for the specific permission this session's work is
 * about: seeing/using the full accounting module (all v2 reports, ledgers,
 * expense/asset entry, loan management, payment gateway settlement, etc.).
 * login.role='0' (super-admin) always passes, regardless of any explicit
 * grant, since that's the existing top-level admin bypass already used
 * elsewhere in this codebase (see elements/aside.php, portal-links.php).
 */
function hasFullAccountingAccess(PDO $pdo, ?string $employeeSysId = null): bool
{
    if (($_SESSION['role'] ?? null) === '0') {
        return true; // existing super-admin convention, unconditional bypass
    }
    $employeeSysId = $employeeSysId ?? ($_SESSION['user_id'] ?? '');
    return hasPermission($pdo, $employeeSysId, 'full_accounting_access');
}

/**
 * Call this at the top of any accounting page/endpoint that should be
 * restricted. Ends the request immediately (JSON for API endpoints, a
 * plain message for pages) if the current session lacks access -- callers
 * don't need to remember to check the return value.
 *
 * $isApi: true => respond with JSON + HTTP 403 (for api/*.php files)
 *         false => die() with a plain message (for pages/*.php files)
 */
function requireFullAccountingAccess(PDO $pdo, bool $isApi = true): void
{
    if (hasFullAccountingAccess($pdo)) return;

    if ($isApi) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You do not have permission to access full accounting features.']);
    } else {
        http_response_code(403);
        die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;">
                <h2 style="color:#dc2626;">Access Restricted</h2>
                <p>You do not have permission to view this page. Contact your administrator if you believe this is a mistake.</p>
            </div>');
    }
    exit;
}