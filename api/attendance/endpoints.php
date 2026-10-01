<?php
// ============================================================
// TravHub Gen-3 — Attendance API
// POST /api/attendance/endpoints.php
// Body: { action, ...params }
// ============================================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../server/db_connection.php';
require '../../server/permissions.php';

date_default_timezone_set('Asia/Dhaka');

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
function jsonOk(array $data): void  { echo json_encode(['success'=>true] + $data); exit; }
function jsonErr(string $msg, int $code=400): void {
    http_response_code($code);
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
}

/**
 * Build a full month's calendar data for one employee.
 * Returns array of day objects:
 *   { date, day_of_week, is_weekend, is_holiday, holiday_title,
 *     att_status, check_in, check_out, note, leave_sys_id,
 *     leave_name, leave_code, leave_color }
 */
function buildMonthCalendar(PDO $pdo, string $empId, int $year, int $month): array {
    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay  = date('Y-m-t', strtotime($firstDay));

    // Fetch attendance records
    $attStmt = $pdo->prepare(
        "SELECT a.*, lt.name AS leave_name, lt.code AS leave_code, lt.color AS leave_color
         FROM attendance a
         LEFT JOIN leave_applications la ON la.sys_id = a.leave_application_sys_id
         LEFT JOIN leave_types lt ON lt.sys_id = la.leave_type_sys_id
         WHERE a.employee_sys_id = ? AND a.date BETWEEN ? AND ?"
    );
    $attStmt->execute([$empId, $firstDay, $lastDay]);
    $attMap = [];
    foreach ($attStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $attMap[$row['date']] = $row;
    }

    // Fetch holidays/closures — office_calendar (legacy) + office_holidays (Gen-3)
    $holMap = [];
    try {
        $holStmt = $pdo->prepare(
            "SELECT date, title, type FROM office_calendar WHERE date BETWEEN ? AND ?"
        );
        $holStmt->execute([$firstDay, $lastDay]);
        foreach ($holStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $holMap[$row['date']] = $row;
        }
    } catch (Throwable $_) {}
    // Gen-3 holidays (table may not exist until migration-v3 is run)
    try {
        $hol2Stmt = $pdo->prepare(
            "SELECT holiday_date AS date, title, type FROM office_holidays WHERE holiday_date BETWEEN ? AND ?"
        );
        $hol2Stmt->execute([$firstDay, $lastDay]);
        foreach ($hol2Stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $holMap[$row['date']] = $row;
        }
    } catch (Throwable $_) {}

    $days   = [];
    $cur    = new DateTime($firstDay);
    $end    = new DateTime($lastDay);
    $today  = date('Y-m-d');

    while ($cur <= $end) {
        $ds  = $cur->format('Y-m-d');
        $dow = (int)$cur->format('N'); // 1=Mon … 7=Sun
        $isWeekend = ($dow === 5); // Friday only
        $isHoliday = isset($holMap[$ds]);
        $isFuture  = $ds > $today;

        $att   = $attMap[$ds]  ?? null;
        $hol   = $holMap[$ds]  ?? null;

        if ($att) {
            $status = $att['status'];
        } elseif ($isWeekend) {
            $status = 'weekend';
        } elseif ($isHoliday) {
            $status = 'holiday';
        } elseif ($isFuture) {
            $status = 'future';
        } else {
            $status = 'not_marked';
        }

        $days[] = [
            'date'          => $ds,
            'day'           => (int)$cur->format('j'),
            'day_of_week'   => $dow,
            'day_name'      => $cur->format('D'),
            'is_weekend'    => $isWeekend,
            'is_holiday'    => $isHoliday,
            'holiday_title' => $hol['title'] ?? null,
            'is_future'     => $isFuture,
            'att_status'    => $status,
            'check_in'      => $att['check_in']   ?? null,
            'check_out'     => $att['check_out']  ?? null,
            'note'          => $att['note']       ?? null,
            'leave_sys_id'  => $att['leave_application_sys_id'] ?? null,
            'leave_name'    => $att['leave_name'] ?? null,
            'leave_code'    => $att['leave_code'] ?? null,
            'leave_color'   => $att['leave_color'] ?? null,
        ];

        $cur->modify('+1 day');
    }

    return $days;
}

// ── Router ────────────────────────────────────────────────
switch ($action) {

    // ── MY MONTH ──────────────────────────────────────────
    case 'my_month': {
        $year  = (int)($body['year']  ?? date('Y'));
        $month = (int)($body['month'] ?? date('m'));

        if ($month < 1 || $month > 12) jsonErr('Invalid month.');

        $days = buildMonthCalendar($pdo, $myId, $year, $month);

        // Summary counts
        $summary = ['present'=>0,'absent'=>0,'late'=>0,'half_day'=>0,'on_leave'=>0,'not_marked'=>0];
        foreach ($days as $d) {
            $s = $d['att_status'];
            if (isset($summary[$s])) $summary[$s]++;
        }

        jsonOk(['data' => $days, 'summary' => $summary, 'year' => $year, 'month' => $month]);
    }

    // ── MONTH FOR ANY EMPLOYEE (HR only) ──────────────────
    case 'month_all': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $empId = trim($body['emp_id'] ?? '');
        $year  = (int)($body['year']  ?? date('Y'));
        $month = (int)($body['month'] ?? date('m'));

        if (!$empId) jsonErr('emp_id is required.');
        if ($month < 1 || $month > 12) jsonErr('Invalid month.');

        // Verify employee exists
        $eStmt = $pdo->prepare("SELECT sys_id, name, JSON_UNQUOTE(JSON_EXTRACT(company_related_info,'$.designation')) AS designation FROM employees WHERE sys_id=?");
        $eStmt->execute([$empId]);
        $emp = $eStmt->fetch(PDO::FETCH_ASSOC);
        if (!$emp) jsonErr('Employee not found.', 404);

        $days = buildMonthCalendar($pdo, $empId, $year, $month);

        $summary = ['present'=>0,'absent'=>0,'late'=>0,'half_day'=>0,'on_leave'=>0,'not_marked'=>0];
        foreach ($days as $d) {
            $s = $d['att_status'];
            if (isset($summary[$s])) $summary[$s]++;
        }

        jsonOk(['data' => $days, 'summary' => $summary, 'employee' => $emp, 'year' => $year, 'month' => $month]);
    }

    // ── MARK ATTENDANCE (HR only) ─────────────────────────
    case 'mark': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $empId    = trim($body['emp_id']   ?? '');
        $date     = trim($body['date']     ?? '');
        $status   = trim($body['status']   ?? 'present');
        $checkIn  = trim($body['check_in'] ?? '') ?: null;
        $checkOut = trim($body['check_out']?? '') ?: null;
        $note     = trim($body['note']     ?? '') ?: null;

        if (!$empId || !$date) jsonErr('emp_id and date are required.');

        $allowed = ['present','absent','late','half_day','on_leave','holiday','weekend'];
        if (!in_array($status, $allowed)) jsonErr('Invalid status.');

        // Validate date
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt) jsonErr('Invalid date format.');

        $sysId = 'ATT-' . strtoupper(uniqid());

        $pdo->prepare(
            "INSERT INTO attendance (sys_id, employee_sys_id, date, check_in, check_out, status, note, marked_by)
             VALUES (?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
               check_in=VALUES(check_in), check_out=VALUES(check_out),
               status=VALUES(status), note=VALUES(note), marked_by=VALUES(marked_by)"
        )->execute([$sysId, $empId, $date, $checkIn, $checkOut, $status, $note, $myId]);

        jsonOk(['message' => 'Attendance marked.', 'date' => $date, 'status' => $status]);
    }

    // ── ALL EMPLOYEES SUMMARY FOR A DATE (HR only) ────────
    case 'daily_all': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $date = trim($body['date'] ?? date('Y-m-d'));
        $dt   = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt) jsonErr('Invalid date.');

        $stmt = $pdo->prepare(
            "SELECT e.sys_id, e.sys_id AS emp_id, e.name,
                    JSON_UNQUOTE(JSON_EXTRACT(e.company_related_info,'$.designation')) AS designation,
                    a.status AS att_status, a.check_in, a.check_out, a.note
             FROM employees e
             LEFT JOIN attendance a ON a.employee_sys_id=e.sys_id AND a.date=?
             WHERE e.status='active'
             ORDER BY e.name"
        );
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich: is today a weekend? (Friday only)
        $dow = (int)$dt->format('N');
        $isWeekend = ($dow === 5);
        $isHoliday = false;
        try {
            $hStmt = $pdo->prepare("SELECT COUNT(*) FROM office_calendar WHERE date=? AND type IN ('holiday','office_closed')");
            $hStmt->execute([$date]);
            $isHoliday = (bool)$hStmt->fetchColumn();
        } catch (Throwable $_) {}
        if (!$isHoliday) {
            try {
                $hStmt2 = $pdo->prepare("SELECT COUNT(*) FROM office_holidays WHERE holiday_date=?");
                $hStmt2->execute([$date]);
                $isHoliday = (bool)$hStmt2->fetchColumn();
            } catch (Throwable $_) {}
        }

        foreach ($rows as &$r) {
            if (!$r['att_status']) {
                $r['att_status'] = $isWeekend ? 'weekend' : ($isHoliday ? 'holiday' : 'not_marked');
            }
        }

        jsonOk(['data' => $rows, 'date' => $date, 'is_weekend' => $isWeekend, 'is_holiday' => $isHoliday]);
    }

    // ── TODAY STATUS (self) ───────────────────────────────
    case 'today_status': {
        $today = date('Y-m-d');
        $stmt  = $pdo->prepare(
            "SELECT a.*, lt.name AS leave_name, lt.code AS leave_code, lt.color AS leave_color
             FROM attendance a
             LEFT JOIN leave_applications la ON la.sys_id = a.leave_application_sys_id
             LEFT JOIN leave_types lt ON lt.sys_id = la.leave_type_sys_id
             WHERE a.employee_sys_id = ? AND a.date = ?"
        );
        $stmt->execute([$myId, $today]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);

        $dow       = (int)date('N');
        $isWeekend = ($dow === 5); // Friday only

        $hStmt = $pdo->prepare("SELECT title FROM office_calendar WHERE date=? AND type IN ('holiday','office_closed')");
        $hStmt->execute([$today]);
        $holRow = $hStmt->fetch(PDO::FETCH_ASSOC);
        if (!$holRow) {
            $hStmt2 = $pdo->prepare("SELECT title FROM office_holidays WHERE holiday_date=?");
            $hStmt2->execute([$today]);
            $holRow = $hStmt2->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        $isHoliday = (bool)$holRow;

        $workedSeconds = null;
        if ($att && $att['check_in'] && $att['check_out']) {
            $in  = strtotime($today . ' ' . $att['check_in']);
            $out = strtotime($today . ' ' . $att['check_out']);
            $workedSeconds = max(0, $out - $in);
        }

        jsonOk([
            'date'           => $today,
            'is_weekend'     => $isWeekend,
            'is_holiday'     => $isHoliday,
            'holiday_title'  => $holRow['title'] ?? null,
            'att_status'     => $att['status']    ?? ($isWeekend ? 'weekend' : ($isHoliday ? 'holiday' : 'not_marked')),
            'check_in'       => $att['check_in']  ?? null,
            'check_out'      => $att['check_out'] ?? null,
            'worked_seconds' => $workedSeconds,
            'note'           => $att['note']      ?? null,
            'leave_name'     => $att['leave_name'] ?? null,
        ]);
    }

    // ── CHECK IN (self — WiFi / fingerprint-ready) ────────
    case 'checkin': {
        $today = date('Y-m-d');
        $now   = date('H:i:s');

        // Check already checked in
        $stmt = $pdo->prepare("SELECT sys_id, check_in, check_out, status FROM attendance WHERE employee_sys_id=? AND date=?");
        $stmt->execute([$myId, $today]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing && $existing['check_in']) {
            jsonOk([
                'message'   => 'Already checked in.',
                'check_in'  => $existing['check_in'],
                'check_out' => $existing['check_out'],
                'already'   => true,
            ]);
        }

        // Determine status: if after 9:30 AM → late
        $lateThreshold = '09:30:00';
        $status = ($now > $lateThreshold) ? 'late' : 'present';

        if ($existing) {
            // Row exists (e.g. marked as on_leave or absent by HR) — update check_in only if status allows
            if (in_array($existing['status'], ['on_leave','holiday','weekend'])) {
                jsonErr('Cannot check in on a ' . $existing['status'] . ' day.');
            }
            $pdo->prepare(
                "UPDATE attendance SET check_in=?, status=?, marked_by=? WHERE sys_id=?"
            )->execute([$now, $status, $myId, $existing['sys_id']]);
        } else {
            $sysId = 'ATT-' . strtoupper(uniqid());
            $pdo->prepare(
                "INSERT INTO attendance (sys_id, employee_sys_id, date, check_in, status, marked_by)
                 VALUES (?,?,?,?,?,?)"
            )->execute([$sysId, $myId, $today, $now, $status, $myId]);
        }

        jsonOk(['message' => 'Checked in.', 'check_in' => $now, 'status' => $status]);
    }

    // ── CHECK OUT (self) ──────────────────────────────────
    case 'checkout': {
        $today = date('Y-m-d');
        $now   = date('H:i:s');

        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_sys_id=? AND date=?");
        $stmt->execute([$myId, $today]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$att || !$att['check_in']) {
            jsonErr('No check-in found for today.');
        }
        if ($att['check_out']) {
            jsonOk(['message' => 'Already checked out.', 'check_out' => $att['check_out'], 'already' => true]);
        }

        $in  = strtotime($today . ' ' . $att['check_in']);
        $out = strtotime($today . ' ' . $now);
        $workedSeconds = max(0, $out - $in);

        $pdo->prepare(
            "UPDATE attendance SET check_out=? WHERE sys_id=?"
        )->execute([$now, $att['sys_id']]);

        jsonOk([
            'message'        => 'Checked out.',
            'check_in'       => $att['check_in'],
            'check_out'      => $now,
            'worked_seconds' => $workedSeconds,
        ]);
    }

    default:
        jsonErr("Unknown action: $action");
}