<?php
/**
 * FILE PATH: /api/admin/migrate-employee-departments.php
 * ONE-TIME MIGRATION SCRIPT
 *
 * Fills employees.department_sys_id using two strategies, in order:
 *   1. Match employees.department_name (text) against departments.name (case-insensitive)
 *   2. Fallback: match employees.department_id (legacy int) against the known
 *      hardcoded legacy department list, then resolve to departments.sys_id
 *
 * Legacy department_id list (hardcoded in the old employees module):
 *   1=Management, 2=Visa, 3=Package, 4=Ticket, 5=IT, 6=Student, 7=Medical, 8=Account
 *
 * Run once via browser or CLI, then this script can be deleted.
 *
 * GET ?dry_run=1   → preview matches without writing (recommended first run)
 * GET (no params)  → actually performs the update
 */
session_start();
header('Content-Type: application/json');
require_once '../../server/db_connection.php';

$dryRun = isset($_GET['dry_run']);

// Legacy hardcoded department_id → name mapping (from the old employees form)
const LEGACY_DEPT_ID_MAP = [
    1 => 'Management',
    2 => 'Visa',
    3 => 'Package',
    4 => 'Ticket',
    5 => 'IT',
    6 => 'Student',
    7 => 'Medical',
    8 => 'Account',
];

try {
    // Fetch all active departments
    $deptStmt = $pdo->query("SELECT sys_id, name FROM departments WHERE is_active = 1");
    $depts = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

    // Build lowercase-trimmed lookup map: name → sys_id
    $deptMap = [];
    foreach ($depts as $d) {
        $deptMap[strtolower(trim($d['name']))] = $d['sys_id'];
    }

    // Fetch employees that still need migration
    $empStmt = $pdo->query("
        SELECT sys_id, name, department_name, department_id, department_sys_id
        FROM employees
        WHERE department_sys_id IS NULL
    ");
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

    $matched   = [];
    $unmatched = [];

    foreach ($employees as $e) {
        $resolvedSysId = null;
        $matchedVia    = null;

        // ── Strategy 1: text match on department_name ──
        if (!empty($e['department_name'])) {
            $key = strtolower(trim($e['department_name']));
            if (isset($deptMap[$key])) {
                $resolvedSysId = $deptMap[$key];
                $matchedVia    = 'department_name';
            }
        }

        // ── Strategy 2: fallback via legacy department_id ──
        if (!$resolvedSysId && !empty($e['department_id']) && isset(LEGACY_DEPT_ID_MAP[(int)$e['department_id']])) {
            $legacyName = LEGACY_DEPT_ID_MAP[(int)$e['department_id']];
            $key = strtolower(trim($legacyName));
            if (isset($deptMap[$key])) {
                $resolvedSysId = $deptMap[$key];
                $matchedVia    = 'department_id (legacy: ' . $legacyName . ')';
            }
        }

        if ($resolvedSysId) {
            $matched[] = [
                'employee_sys_id'     => $e['sys_id'],
                'employee_name'       => $e['name'],
                'department_name'     => $e['department_name'],
                'department_id'       => $e['department_id'],
                'matched_via'         => $matchedVia,
                'matched_dept_sys_id' => $resolvedSysId,
            ];
        } else {
            $unmatched[] = [
                'employee_sys_id' => $e['sys_id'],
                'employee_name'   => $e['name'],
                'department_name' => $e['department_name'],
                'department_id'   => $e['department_id'],
            ];
        }
    }

    if (!$dryRun && !empty($matched)) {
        $update = $pdo->prepare("UPDATE employees SET department_sys_id = ? WHERE sys_id = ?");
        foreach ($matched as $m) {
            $update->execute([$m['matched_dept_sys_id'], $m['employee_sys_id']]);
        }
    }

    echo json_encode([
        'status'             => 'success',
        'mode'               => $dryRun ? 'dry_run (no changes written)' : 'applied',
        'departments_found'  => count($depts),
        'departments_loaded' => array_map(fn($d) => $d['name'], $depts),
        'matched_count'      => count($matched),
        'unmatched_count'    => count($unmatched),
        'matched'   => $matched,
        'unmatched' => $unmatched, // these need manual review — no department_name or department_id resolved
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}