<?php
// ============================================================
// TravHub Gen-3 — Payroll API
// POST /api/payroll/endpoints.php
// Body: { action, ...params }
//
// list   — employee sees their own slips (HR can pass employee_id)
// get    — single slip detail by sys_id
// ============================================================

session_start();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../server/db_connection.php';
require_once '../../server/permissions.php';

if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}

$myId  = $_SESSION['user_id']   ?? '';
$isHR  = ($_SESSION['user_role'] ?? '') === '0' || canAccess($pdo, 'hrm_employee_view');

function jsonOk(array $d): void  { echo json_encode(['success' => true] + $d); exit; }
function jsonErr(string $m, int $c = 400): void {
    http_response_code($c);
    echo json_encode(['success' => false, 'message' => $m]);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim($body['action'] ?? $_GET['action'] ?? '');

switch ($action) {

    // ── LIST ─────────────────────────────────────────────────
    case 'list': {
        // HR can query any employee; employees only see themselves
        $empId  = ($isHR && !empty($body['employee_id'])) ? trim($body['employee_id']) : $myId;
        $year   = (int)($body['year']  ?? date('Y'));
        $limit  = min((int)($body['limit'] ?? 24), 60);

        $stmt = $pdo->prepare(
            "SELECT sys_id, employee_id, employee_name,
                    net_payable_salary, month, payment_date,
                    payment_type, status,
                    bonus, overtime, allowances, deduction,
                    eps_salary
             FROM payroll_finals
             WHERE employee_id = ?
               AND YEAR(STR_TO_DATE(month, '%Y-%m')) = ?
             ORDER BY month DESC
             LIMIT ?"
        );
        $stmt->execute([$empId, $year, $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            // Decode eps_salary JSON if present
            if (is_string($r['eps_salary'])) {
                $r['eps_salary'] = json_decode($r['eps_salary'], true) ?? [];
            }
        }
        unset($r);

        // Also get years available for this employee
        $yStmt = $pdo->prepare(
            "SELECT DISTINCT YEAR(STR_TO_DATE(month,'%Y-%m')) AS yr
             FROM payroll_finals
             WHERE employee_id = ?
             ORDER BY yr DESC LIMIT 10"
        );
        $yStmt->execute([$empId]);
        $years = $yStmt->fetchAll(PDO::FETCH_COLUMN);

        jsonOk(['data' => $rows, 'years' => $years, 'year' => $year]);
    }

    // ── GET (single slip detail) ──────────────────────────────
    case 'get': {
        $sysId = trim($body['sys_id'] ?? '');
        if (!$sysId) jsonErr('sys_id required.');

        $stmt = $pdo->prepare("SELECT * FROM payroll_finals WHERE sys_id=?");
        $stmt->execute([$sysId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) jsonErr('Not found.', 404);

        // Security: non-HR employees can only view their own slips
        if (!$isHR && $row['employee_id'] !== $myId) {
            jsonErr('Permission denied.', 403);
        }

        // Decode JSON columns
        foreach (['eps_salary', 'allowances', 'deduction', 'payment_components', 'prepared_info', 'collected_info', 'authorized_info', 'meta_data'] as $col) {
            if (isset($row[$col]) && is_string($row[$col])) {
                $row[$col] = json_decode($row[$col], true) ?? [];
            }
        }

        jsonOk(['data' => $row]);
    }

    default:
        jsonErr("Unknown action: $action");
}