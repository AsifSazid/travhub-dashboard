<?php
// ============================================================
// TravHub Gen-3 — Employee Notes API
// POST /api/notes/endpoints.php
// Notes are private to the employee — HR cannot see them.
//
// Actions:
//   month      — all notes for a month keyed by date (array per date)
//   list_date  — all notes for a specific date
//   add        — create new note {date, title?, note_text}
//   update     — edit existing {sys_id, title?, note_text}
//   delete     — remove {sys_id}
// ============================================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../server/db_connection.php';

if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}
$myId = $_SESSION['user_id'] ?? '';

function jsonOk(array $data): void { echo json_encode(['success'=>true] + $data); exit; }
function jsonErr(string $msg, int $code=400): void {
    http_response_code($code);
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
}

// Graceful schema upgrades (idempotent)
try { $pdo->query("SELECT title FROM employee_notes LIMIT 1"); }
catch (Throwable $_) {
    try { $pdo->exec("ALTER TABLE employee_notes ADD COLUMN title VARCHAR(200) NULL AFTER note_date"); } catch (Throwable $_) {}
}
try { $pdo->query("SELECT repeat_yearly FROM employee_notes LIMIT 1"); }
catch (Throwable $_) {
    try { $pdo->exec("ALTER TABLE employee_notes ADD COLUMN repeat_yearly TINYINT(1) NOT NULL DEFAULT 0 AFTER title"); } catch (Throwable $_) {}
}
try { $pdo->exec("ALTER TABLE employee_notes DROP INDEX uq_en_emp_date"); } catch (Throwable $_) {}

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = trim($body['action'] ?? $_GET['action'] ?? '');

switch ($action) {

    // ── GET ALL NOTES FOR A MONTH (grouped by date) ───────
    case 'month': {
        $year  = (int)($body['year']  ?? date('Y'));
        $month = (int)($body['month'] ?? date('m'));
        $from  = sprintf('%04d-%02d-01', $year, $month);
        $to    = date('Y-m-t', strtotime($from));

        // Direct notes for this month
        $stmt = $pdo->prepare(
            "SELECT sys_id, note_date, title, repeat_yearly, note_text, created_at, updated_at
             FROM employee_notes
             WHERE employee_sys_id = ? AND note_date BETWEEN ? AND ?
             ORDER BY note_date ASC, created_at ASC"
        );
        $stmt->execute([$myId, $from, $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also include repeat_yearly notes from OTHER years that fall in this month
        $mmStr = sprintf('%02d', $month);
        try {
            $rStmt = $pdo->prepare(
                "SELECT sys_id, note_date, title, repeat_yearly, note_text, created_at, updated_at
                 FROM employee_notes
                 WHERE employee_sys_id = ? AND repeat_yearly = 1
                   AND MONTH(note_date) = ?
                   AND YEAR(note_date) != ?
                 ORDER BY created_at ASC"
            );
            $rStmt->execute([$myId, $month, $year]);
            foreach ($rStmt->fetchAll(PDO::FETCH_ASSOC) as $rRow) {
                // Rewrite note_date to the requested year
                $rRow['note_date']      = sprintf('%04d-%s-%s', $year, $mmStr, substr($rRow['note_date'], 8, 2));
                $rRow['is_recurring']   = true;  // UI hint
                $rows[] = $rRow;
            }
        } catch (Throwable $_) {}

        // Group by date
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['note_date']][] = $row;
        }
        $hasNote = [];
        foreach ($grouped as $d => $_) { $hasNote[$d] = true; }

        jsonOk(['data' => $grouped, 'has_note' => $hasNote, 'year' => $year, 'month' => $month]);
    }

    // ── GET ALL NOTES FOR ONE DATE ────────────────────────
    case 'list_date': {
        $date = trim($body['date'] ?? '');
        if (!$date) jsonErr('date required.');

        $stmt = $pdo->prepare(
            "SELECT sys_id, note_date, title, repeat_yearly, note_text, created_at, updated_at
             FROM employee_notes
             WHERE employee_sys_id = ? AND note_date = ?
             ORDER BY created_at ASC"
        );
        $stmt->execute([$myId, $date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also include recurring notes from other years on same month-day
        $mmdd = substr($date, 5); // MM-DD
        try {
            $rStmt = $pdo->prepare(
                "SELECT sys_id, note_date, title, repeat_yearly, note_text, created_at, updated_at
                 FROM employee_notes
                 WHERE employee_sys_id = ? AND repeat_yearly = 1
                   AND DATE_FORMAT(note_date,'%m-%d') = ?
                   AND note_date != ?
                 ORDER BY created_at ASC"
            );
            $rStmt->execute([$myId, $mmdd, $date]);
            foreach ($rStmt->fetchAll(PDO::FETCH_ASSOC) as $rRow) {
                $rRow['note_date']    = $date;
                $rRow['is_recurring'] = true;
                $rows[] = $rRow;
            }
        } catch (Throwable $_) {}

        jsonOk(['data' => $rows, 'date' => $date]);
    }

    // ── ADD NEW NOTE ──────────────────────────────────────
    case 'add': {
        $date          = trim($body['date']         ?? '');
        $text          = trim($body['note_text']    ?? '');
        $title         = trim($body['title']        ?? '');
        $repeatYearly  = !empty($body['repeat_yearly']) ? 1 : 0;

        if (!$date) jsonErr('date is required.');
        if (!$text && !$title) jsonErr('title or note_text is required.');

        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt) jsonErr('Invalid date format.');

        $sysId = 'NOTE-' . strtoupper(uniqid());
        $pdo->prepare(
            "INSERT INTO employee_notes (sys_id, employee_sys_id, note_date, title, repeat_yearly, note_text)
             VALUES (?,?,?,?,?,?)"
        )->execute([$sysId, $myId, $date, $title ?: null, $repeatYearly, $text]);

        $row = $pdo->prepare("SELECT sys_id, note_date, title, repeat_yearly, note_text, created_at FROM employee_notes WHERE sys_id=?");
        $row->execute([$sysId]);
        jsonOk(['message' => 'Note added.', 'note' => $row->fetch(PDO::FETCH_ASSOC), 'date' => $date]);
    }

    // ── UPDATE EXISTING NOTE ──────────────────────────────
    case 'update': {
        $sysId        = trim($body['sys_id']     ?? '');
        $text         = trim($body['note_text']  ?? '');
        $title        = trim($body['title']      ?? '');
        $repeatYearly = isset($body['repeat_yearly']) ? (int)(!empty($body['repeat_yearly'])) : null;

        if (!$sysId) jsonErr('sys_id is required.');

        $own = $pdo->prepare("SELECT sys_id FROM employee_notes WHERE sys_id=? AND employee_sys_id=?");
        $own->execute([$sysId, $myId]);
        if (!$own->fetch()) jsonErr('Not found or permission denied.', 404);

        if ($repeatYearly !== null) {
            $pdo->prepare("UPDATE employee_notes SET title=?, note_text=?, repeat_yearly=? WHERE sys_id=?")
                ->execute([$title ?: null, $text, $repeatYearly, $sysId]);
        } else {
            $pdo->prepare("UPDATE employee_notes SET title=?, note_text=? WHERE sys_id=?")
                ->execute([$title ?: null, $text, $sysId]);
        }

        jsonOk(['message' => 'Note updated.']);
    }

    // ── DELETE NOTE ───────────────────────────────────────
    case 'delete': {
        $sysId = trim($body['sys_id'] ?? '');
        if (!$sysId) jsonErr('sys_id is required.');

        $own = $pdo->prepare("SELECT sys_id FROM employee_notes WHERE sys_id=? AND employee_sys_id=?");
        $own->execute([$sysId, $myId]);
        if (!$own->fetch()) jsonErr('Not found or permission denied.', 404);

        $pdo->prepare("DELETE FROM employee_notes WHERE sys_id=?")->execute([$sysId]);
        jsonOk(['message' => 'Note deleted.']);
    }

    // Legacy compat: old 'save' action → upsert single note
    case 'save': {
        $date  = trim($body['date']      ?? '');
        $text  = trim($body['note_text'] ?? '');
        if (!$date) jsonErr('date is required.');
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt) jsonErr('Invalid date format.');
        if ($text === '') {
            // Delete all notes for this date (legacy behaviour)
            $pdo->prepare("DELETE FROM employee_notes WHERE employee_sys_id=? AND note_date=?")->execute([$myId, $date]);
            jsonOk(['message' => 'Notes cleared.', 'date' => $date]);
        }
        $sysId = 'NOTE-' . strtoupper(uniqid());
        $pdo->prepare(
            "INSERT INTO employee_notes (sys_id, employee_sys_id, note_date, note_text) VALUES (?,?,?,?)"
        )->execute([$sysId, $myId, $date, $text]);
        jsonOk(['message' => 'Note saved.', 'date' => $date]);
    }

    default:
        jsonErr("Unknown action: $action");
}