<?php
// PATH: /server/hrm_permissions.php
//
// HRM-specific permission helpers. Include this after db_connection.php
// on any HRM page or API that needs permission checks.
//
// Usage (page):
//   require_once __DIR__ . '/../server/hrm_permissions.php';
//   requireHrm($pdo, 'hrm_employee_view', false);
//
// Usage (API):
//   require_once __DIR__ . '/../../server/hrm_permissions.php';
//   requireHrm($pdo, 'eps_manage');

require_once __DIR__ . '/permissions.php';

/**
 * Guard an HRM page or API. Thin wrapper around requirePermission()
 * so HRM files only need one include.
 *
 * $isApi = true  → JSON 403 (API files)
 * $isApi = false → HTML access-restricted page (pages/*.php)
 */
function requireHrm(PDO $pdo, string $permissionKey, bool $isApi = true): void
{
    requirePermission($pdo, $permissionKey, $isApi);
}

/**
 * Allows access to a generate-*.php doc page if EITHER:
 *   (a) the user holds $permissionKey (HR staff / manager), OR
 *   (b) the requested employee_id matches the current session user
 *       (self-service — an employee viewing their own document).
 *
 * Call this on pages/generate-*.php instead of requirePermission().
 *
 * $requestedEmpId  — the employee_id from $_GET['employee_id']
 * $permissionKey   — 'hr_docs_generate' or 'hr_id_card'
 */
function requireHrmOrSelf(PDO $pdo, string $requestedEmpId, string $permissionKey): void
{
    // Super-admin always passes (checked inside canAccess too, but be explicit).
    if ((string)($_SESSION['role'] ?? '') === '0') return;

    // HR staff with the right permission → allow.
    if (canAccess($pdo, $permissionKey)) return;

    // Self-service: the logged-in employee is requesting their own document.
    $myId = $_SESSION['user_id'] ?? '';
    if ($myId && $requestedEmpId && $myId === $requestedEmpId) return;

    // Otherwise → deny (page-style HTML error, not JSON).
    http_response_code(403);
    echo '<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;">
            <h2 style="color:#dc2626;">Access Restricted</h2>
            <p>You do not have permission to view this document.</p>
          </div>';
    exit;
}