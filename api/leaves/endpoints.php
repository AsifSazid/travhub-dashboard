<?php
// ============================================================
// TravHub Gen-3 — Leave Management API
// POST /api/leaves/endpoints.php
// Body: { action, ...params }
// ============================================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../server/db_connection.php';
require '../../server/permissions.php';

// ── Auth ──────────────────────────────────────────────────
if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}
$myId   = $_SESSION['user_id']   ?? '';
$myRole = $_SESSION['user_role'] ?? '';
$isHR   = ($myRole === '0') || canAccess($pdo, 'hrm_employee_view');

// ── Input ─────────────────────────────────────────────────
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = trim($body['action'] ?? $_GET['action'] ?? '');

// ── Helpers ───────────────────────────────────────────────

/**
 * Count working days between two dates (inclusive).
 * BD weekend = Friday (5) + Saturday (6).
 * Also excludes dates in office_calendar where type = 'holiday' or 'office_closed'.
 */
function getWorkingDays(PDO $pdo, string $from, string $to): int {
    // Fetch holidays/closures in range
    $stmt = $pdo->prepare(
        "SELECT date FROM office_calendar
         WHERE date BETWEEN ? AND ?
         AND type IN ('holiday','office_closed')"
    );
    $stmt->execute([$from, $to]);
    $holidays = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

    $count = 0;
    $cur   = new DateTime($from);
    $end   = new DateTime($to);
    while ($cur <= $end) {
        $dow = (int)$cur->format('N'); // 1=Mon … 7=Sun
        $ds  = $cur->format('Y-m-d');
        if ($dow !== 5 && !isset($holidays[$ds])) { // Friday = only weekend
            $count++;
        }
        $cur->modify('+1 day');
    }
    return $count;
}

/** Ensure leave_balances row exists; return ['allocated'=>x,'used'=>y,'pending'=>z] */
function ensureBalance(PDO $pdo, string $empId, string $ltId, int $year): array {
    // Auto-init from leave_types.max_days_per_year if not present
    $pdo->prepare(
        "INSERT IGNORE INTO leave_balances
            (employee_sys_id, leave_type_sys_id, year, allocated, used, pending)
         SELECT ?, ?, ?, max_days_per_year, 0, 0
         FROM leave_types WHERE sys_id = ?"
    )->execute([$empId, $ltId, $year, $ltId]);

    $stmt = $pdo->prepare(
        "SELECT allocated, used, pending FROM leave_balances
         WHERE employee_sys_id=? AND leave_type_sys_id=? AND year=?"
    );
    $stmt->execute([$empId, $ltId, $year]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['allocated'=>0,'used'=>0,'pending'=>0];
}

function jsonOk(array $data): void  { echo json_encode(['success'=>true]  + $data); exit; }
function jsonErr(string $msg, int $code=400): void {
    http_response_code($code);
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
}

// ── Router ────────────────────────────────────────────────
switch ($action) {

    // ── LEAVE TYPES ──────────────────────────────────────
    case 'types': {
        $stmt = $pdo->query(
            "SELECT sys_id, name, code, max_days_per_year, carry_forward, is_paid, color
             FROM leave_types WHERE status='active' ORDER BY name"
        );
        jsonOk(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ── MY BALANCES ──────────────────────────────────────
    case 'balances': {
        $year  = (int)($body['year'] ?? date('Y'));
        $empId = $myId;

        // Ensure rows exist for all active types
        $types = $pdo->query("SELECT sys_id FROM leave_types WHERE status='active'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($types as $ltId) {
            ensureBalance($pdo, $empId, $ltId, $year);
        }

        $stmt = $pdo->prepare(
            "SELECT lb.leave_type_sys_id, lt.name, lt.code, lt.color,
                    lb.allocated, lb.used, lb.pending,
                    (lb.allocated - lb.used - lb.pending) AS remaining
             FROM leave_balances lb
             JOIN leave_types lt ON lt.sys_id COLLATE utf8mb4_unicode_ci = lb.leave_type_sys_id COLLATE utf8mb4_unicode_ci
             WHERE lb.employee_sys_id = ? AND lb.year = ?
             ORDER BY lt.name"
        );
        $stmt->execute([$empId, $year]);
        jsonOk(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'year' => $year]);
    }

    // ── APPLY FOR LEAVE ──────────────────────────────────
    case 'apply': {
        $ltId   = trim($body['leave_type_sys_id'] ?? '');
        $from   = trim($body['date_from'] ?? '');
        $to     = trim($body['date_to']   ?? '');
        $reason = trim($body['reason']    ?? '');
        $empId  = $myId;

        if (!$ltId || !$from || !$to) jsonErr('Leave type, date_from and date_to are required.');

        $dtFrom = DateTime::createFromFormat('Y-m-d', $from);
        $dtTo   = DateTime::createFromFormat('Y-m-d', $to);
        if (!$dtFrom || !$dtTo || $dtFrom > $dtTo) jsonErr('Invalid date range.');

        $year         = (int)$dtFrom->format('Y');
        $calDays      = (int)$dtFrom->diff($dtTo)->days + 1;
        $workingDays  = getWorkingDays($pdo, $from, $to);
        $bridgedDays  = $calDays - $workingDays;   // weekends/holidays bridged

        if ($workingDays < 1) jsonErr('No working days in the selected range.');

        // Check for overlapping pending/approved applications
        $overlap = $pdo->prepare(
            "SELECT COUNT(*) FROM leave_applications
             WHERE employee_sys_id = ?
               AND status IN ('pending','approved')
               AND date_from <= ? AND date_to >= ?"
        );
        $overlap->execute([$empId, $to, $from]);
        if ((int)$overlap->fetchColumn() > 0) jsonErr('You already have an overlapping leave application.');

        // Check balance
        $bal = ensureBalance($pdo, $empId, $ltId, $year);
        $available = $bal['allocated'] - $bal['used'] - $bal['pending'];
        if ($workingDays > $available) {
            jsonErr("Insufficient balance. You have $available day(s) remaining but requested $workingDays working day(s).");
        }

        // Fetch leave type
        $lt = $pdo->prepare("SELECT * FROM leave_types WHERE sys_id=?")->execute([$ltId]);
        $ltRow = $pdo->prepare("SELECT * FROM leave_types WHERE sys_id=?");
        $ltRow->execute([$ltId]);
        $ltData = $ltRow->fetch(PDO::FETCH_ASSOC);
        if (!$ltData) jsonErr('Invalid leave type.');

        $sysId = 'LA-' . strtoupper(uniqid());

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO leave_applications
                    (sys_id, employee_sys_id, leave_type_sys_id, date_from, date_to,
                     total_days, bridged_days, reason, status)
                 VALUES (?,?,?,?,?,?,?,?,'pending')"
            )->execute([$sysId, $empId, $ltId, $from, $to, $workingDays, $bridgedDays, $reason]);

            // Increment pending balance
            $pdo->prepare(
                "UPDATE leave_balances SET pending = pending + ?
                 WHERE employee_sys_id=? AND leave_type_sys_id=? AND year=?"
            )->execute([$workingDays, $empId, $ltId, $year]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonErr('Failed to apply leave: ' . $e->getMessage(), 500);
        }

        jsonOk([
            'message'      => 'Leave application submitted successfully.',
            'sys_id'       => $sysId,
            'working_days' => $workingDays,
            'bridged_days' => $bridgedDays,
        ]);
    }

    // ── MY LEAVE LIST ─────────────────────────────────────
    case 'list': {
        $year  = (int)($body['year'] ?? date('Y'));
        $empId = $myId;

        $stmt = $pdo->prepare(
            "SELECT la.*, lt.name AS leave_name, lt.code AS leave_code, lt.color,
                    e.name AS reviewed_by_name
             FROM leave_applications la
             JOIN leave_types lt ON lt.sys_id COLLATE utf8mb4_unicode_ci = la.leave_type_sys_id COLLATE utf8mb4_unicode_ci
             LEFT JOIN employees e ON e.sys_id COLLATE utf8mb4_unicode_ci = la.reviewed_by COLLATE utf8mb4_unicode_ci
             WHERE la.employee_sys_id = ? AND YEAR(la.date_from) = ?
             ORDER BY la.created_at DESC"
        );
        $stmt->execute([$empId, $year]);
        jsonOk(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'year' => $year]);
    }

    // ── CANCEL (own pending application) ─────────────────
    case 'cancel': {
        $laId  = trim($body['sys_id'] ?? '');
        if (!$laId) jsonErr('sys_id is required.');

        $stmt = $pdo->prepare(
            "SELECT * FROM leave_applications WHERE sys_id=? AND employee_sys_id=? AND status='pending'"
        );
        $stmt->execute([$laId, $myId]);
        $la = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$la) jsonErr('Application not found or cannot be cancelled.', 404);

        $year = (int)(new DateTime($la['date_from']))->format('Y');

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE leave_applications SET status='cancelled' WHERE sys_id=?"
            )->execute([$laId]);

            $pdo->prepare(
                "UPDATE leave_balances SET pending = GREATEST(0, pending - ?)
                 WHERE employee_sys_id=? AND leave_type_sys_id=? AND year=?"
            )->execute([$la['total_days'], $myId, $la['leave_type_sys_id'], $year]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonErr('Failed to cancel: ' . $e->getMessage(), 500);
        }

        jsonOk(['message' => 'Leave application cancelled.']);
    }

    // ── APPROVE (HR only) ─────────────────────────────────
    case 'approve': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $laId = trim($body['sys_id'] ?? '');
        $note = trim($body['note']   ?? '');
        if (!$laId) jsonErr('sys_id is required.');

        $stmt = $pdo->prepare(
            "SELECT * FROM leave_applications WHERE sys_id=? AND status='pending'"
        );
        $stmt->execute([$laId]);
        $la = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$la) jsonErr('Application not found or not pending.', 404);

        $year = (int)(new DateTime($la['date_from']))->format('Y');

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE leave_applications
                 SET status='approved', reviewed_by=?, review_note=?, reviewed_at=NOW()
                 WHERE sys_id=?"
            )->execute([$myId, $note, $laId]);

            // Move from pending → used in balance
            $pdo->prepare(
                "UPDATE leave_balances
                 SET used    = used    + ?,
                     pending = GREATEST(0, pending - ?)
                 WHERE employee_sys_id=? AND leave_type_sys_id=? AND year=?"
            )->execute([$la['total_days'], $la['total_days'],
                        $la['employee_sys_id'], $la['leave_type_sys_id'], $year]);

            // Mark attendance rows as on_leave for working days
            $cur = new DateTime($la['date_from']);
            $end = new DateTime($la['date_to']);
            $holidays = [];
            $hStmt = $pdo->prepare(
                "SELECT date FROM office_calendar WHERE date BETWEEN ? AND ? AND type IN ('holiday','office_closed')"
            );
            $hStmt->execute([$la['date_from'], $la['date_to']]);
            $holidays = array_flip($hStmt->fetchAll(PDO::FETCH_COLUMN));
            // Also merge Gen-3 office_holidays
            $hStmt3 = $pdo->prepare(
                "SELECT holiday_date FROM office_holidays WHERE holiday_date BETWEEN ? AND ?"
            );
            $hStmt3->execute([$la['date_from'], $la['date_to']]);
            foreach ($hStmt3->fetchAll(PDO::FETCH_COLUMN) as $hd) { $holidays[$hd] = true; }

            while ($cur <= $end) {
                $dow = (int)$cur->format('N');
                $ds  = $cur->format('Y-m-d');
                if ($dow !== 5 && !isset($holidays[$ds])) { // Friday = only weekend
                    $attId = 'ATT-' . strtoupper(uniqid());
                    $pdo->prepare(
                        "INSERT INTO attendance
                            (sys_id, employee_sys_id, date, status, leave_application_sys_id, marked_by)
                         VALUES (?,?,?,'on_leave',?,?)
                         ON DUPLICATE KEY UPDATE
                            status='on_leave', leave_application_sys_id=VALUES(leave_application_sys_id), marked_by=VALUES(marked_by)"
                    )->execute([$attId, $la['employee_sys_id'], $ds, $laId, $myId]);
                }
                $cur->modify('+1 day');
            }

            // Notification: leave approved (best-effort — won't roll back if notif table missing)
            try {
                $ltStmtN = $pdo->prepare("SELECT name FROM leave_types WHERE sys_id=?");
                $ltStmtN->execute([$la['leave_type_sys_id']]);
                $ltNameN  = $ltStmtN->fetchColumn() ?: 'Leave';
                $nBody    = $note ? "Note: $note" : null;
                $nId      = 'NOTIF-' . strtoupper(uniqid());
                $pdo->prepare(
                    "INSERT INTO employee_notifications (sys_id, employee_sys_id, title, body, type, ref_sys_id)
                     VALUES (?,?,?,?,'leave',?)"
                )->execute([$nId, $la['employee_sys_id'],
                            "✅ Your $ltNameN application has been approved.", $nBody, $laId]);
            } catch (Throwable $_) {}

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonErr('Failed to approve: ' . $e->getMessage(), 500);
        }

        jsonOk(['message' => 'Leave approved.']);
    }

    // ── REJECT (HR only) ──────────────────────────────────
    case 'reject': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $laId = trim($body['sys_id'] ?? '');
        $note = trim($body['note']   ?? '');
        if (!$laId) jsonErr('sys_id is required.');

        $stmt = $pdo->prepare(
            "SELECT * FROM leave_applications WHERE sys_id=? AND status='pending'"
        );
        $stmt->execute([$laId]);
        $la = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$la) jsonErr('Application not found or not pending.', 404);

        $year = (int)(new DateTime($la['date_from']))->format('Y');

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE leave_applications
                 SET status='rejected', reviewed_by=?, review_note=?, reviewed_at=NOW()
                 WHERE sys_id=?"
            )->execute([$myId, $note, $laId]);

            $pdo->prepare(
                "UPDATE leave_balances
                 SET pending = GREATEST(0, pending - ?)
                 WHERE employee_sys_id=? AND leave_type_sys_id=? AND year=?"
            )->execute([$la['total_days'], $la['employee_sys_id'], $la['leave_type_sys_id'], $year]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonErr('Failed to reject: ' . $e->getMessage(), 500);
        }

        // Notification: leave rejected (best-effort — won't roll back if notif table missing)
        try {
            $ltStmt2 = $pdo->prepare("SELECT name FROM leave_types WHERE sys_id=?");
            $ltStmt2->execute([$la['leave_type_sys_id']]);
            $ltName2   = $ltStmt2->fetchColumn() ?: 'Leave';
            $noteBody2 = $note ? "Reason: $note" : null;
            $nId2      = 'NOTIF-' . strtoupper(uniqid());
            $pdo->prepare(
                "INSERT INTO employee_notifications (sys_id, employee_sys_id, title, body, type, ref_sys_id)
                 VALUES (?,?,?,?,'leave',?)"
            )->execute([$nId2, $la['employee_sys_id'],
                        "❌ Your $ltName2 application has been rejected.",
                        $noteBody2, $laId]);
        } catch (Throwable $_) {}


        jsonOk(['message' => 'Leave rejected.']);
    }

    // ── LIST ALL (HR only) ────────────────────────────────
    case 'list_all': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $year    = (int)($body['year']   ?? date('Y'));
        $status  = $body['status']        ?? '';
        $empId   = trim($body['emp_id']  ?? '');

        $where  = ['YEAR(la.date_from) = ?'];
        $params = [$year];
        if ($status) { $where[] = 'la.status = ?'; $params[] = $status; }
        if ($empId)  { $where[] = 'la.employee_sys_id = ?'; $params[] = $empId; }

        $stmt = $pdo->prepare(
            "SELECT la.*,
                    lt.name AS leave_name, lt.code AS leave_code, lt.color,
                    emp.name AS employee_name,
                    emp.sys_id AS employee_id,
                    rev.name AS reviewed_by_name
             FROM leave_applications la
             JOIN leave_types lt    ON lt.sys_id  COLLATE utf8mb4_unicode_ci = la.leave_type_sys_id  COLLATE utf8mb4_unicode_ci
             JOIN employees emp     ON emp.sys_id COLLATE utf8mb4_unicode_ci = la.employee_sys_id    COLLATE utf8mb4_unicode_ci
             LEFT JOIN employees rev ON rev.sys_id COLLATE utf8mb4_unicode_ci = la.reviewed_by       COLLATE utf8mb4_unicode_ci
             WHERE " . implode(' AND ', $where) . "
             ORDER BY la.created_at DESC"
        );
        $stmt->execute($params);
        jsonOk(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'year' => $year]);
    }

    // ── SET ALLOCATION (HR only) ──────────────────────────
    // Override leave_balances.allocated for any employee + leave_type + year
    case 'set_allocation': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $empId  = trim($body['emp_id']             ?? '');
        $ltId   = trim($body['leave_type_sys_id']  ?? '');
        $year   = (int)($body['year']              ?? date('Y'));
        $days   = (int)($body['allocated']         ?? -1);

        if (!$empId || !$ltId) jsonErr('emp_id and leave_type_sys_id are required.');
        if ($days < 0)         jsonErr('allocated must be >= 0.');

        // Ensure employee exists
        $eStmt = $pdo->prepare("SELECT sys_id FROM employees WHERE sys_id=?");
        $eStmt->execute([$empId]);
        if (!$eStmt->fetch()) jsonErr('Employee not found.', 404);

        // Ensure leave type exists
        $ltStmt = $pdo->prepare("SELECT sys_id FROM leave_types WHERE sys_id=? AND status='active'");
        $ltStmt->execute([$ltId]);
        if (!$ltStmt->fetch()) jsonErr('Leave type not found.', 404);

        // Upsert
        $pdo->prepare(
            "INSERT INTO leave_balances (employee_sys_id, leave_type_sys_id, year, allocated, used, pending)
             VALUES (?, ?, ?, ?, 0, 0)
             ON DUPLICATE KEY UPDATE allocated = VALUES(allocated)"
        )->execute([$empId, $ltId, $year, $days]);

        // Return updated balance
        $bal = ensureBalance($pdo, $empId, $ltId, $year);
        jsonOk(['message' => 'Allocation updated.', 'balance' => $bal]);
    }

    // ── ALL BALANCES FOR ONE EMPLOYEE (HR only) ───────────
    case 'balances_emp': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $empId = trim($body['emp_id'] ?? '');
        $year  = (int)($body['year'] ?? date('Y'));
        if (!$empId) jsonErr('emp_id is required.');

        // Ensure rows exist for all active types
        $types = $pdo->query("SELECT sys_id FROM leave_types WHERE status='active'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($types as $ltId) {
            ensureBalance($pdo, $empId, $ltId, $year);
        }

        $stmt = $pdo->prepare(
            "SELECT lb.leave_type_sys_id, lt.name, lt.code, lt.color,
                    lb.allocated, lb.used, lb.pending,
                    (lb.allocated - lb.used - lb.pending) AS remaining
             FROM leave_balances lb
             JOIN leave_types lt ON lt.sys_id COLLATE utf8mb4_unicode_ci = lb.leave_type_sys_id COLLATE utf8mb4_unicode_ci
             WHERE lb.employee_sys_id = ? AND lb.year = ?
             ORDER BY lt.name"
        );
        $stmt->execute([$empId, $year]);
        jsonOk(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'year' => $year]);
    }

    default:
        jsonErr("Unknown action: $action");
}