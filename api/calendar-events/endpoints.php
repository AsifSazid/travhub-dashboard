<?php
// ============================================================
// TravHub Gen-3 — Office Calendar Events API
// POST /api/calendar-events/endpoints.php
//
// Actions:
//   list    — events for a year (filtered by visibility for employees)
//   add     — create event {date, title, description?, visibility, visible_to?} (admin only)
//   update  — edit event {sys_id, ...fields} (admin only)
//   delete  — remove event {sys_id} (admin only)
// ============================================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../server/db_connection.php';
require '../../server/permissions.php';

if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}

$myId   = $_SESSION['user_id']   ?? '';
$myRole = $_SESSION['user_role'] ?? '';
$isHR   = ($myRole === '0') || canAccess($pdo, 'hrm_employee_view');

function jsonOk(array $data): void { echo json_encode(['success'=>true] + $data); exit; }
function jsonErr(string $msg, int $code=400): void {
    http_response_code($code);
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
}

// Graceful schema upgrade — create table if not exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS office_calendar_events (
            sys_id       VARCHAR(50)  NOT NULL PRIMARY KEY,
            event_date   DATE         NOT NULL,
            title        VARCHAR(200) NOT NULL,
            description  TEXT         NULL,
            visibility   ENUM('public','private') NOT NULL DEFAULT 'public',
            visible_to   JSON         NULL COMMENT 'Array of employee sys_ids when visibility=private',
            created_by   VARCHAR(50)  NOT NULL,
            created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_event_date (event_date),
            INDEX idx_visibility (visibility)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Throwable $_) {}

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = trim($body['action'] ?? $_GET['action'] ?? '');

switch ($action) {

    // ── LIST EVENTS FOR A YEAR ────────────────────────────
    case 'list': {
        $year  = (int)($body['year'] ?? date('Y'));
        $from  = "$year-01-01";
        $to    = "$year-12-31";

        if ($isHR) {
            // Admin: all events
            $stmt = $pdo->prepare(
                "SELECT e.*, emp.emp_name AS created_by_name
                 FROM office_calendar_events e
                 LEFT JOIN employees emp ON emp.sys_id = e.created_by
                 WHERE e.event_date BETWEEN ? AND ?
                 ORDER BY e.event_date ASC, e.created_at ASC"
            );
            $stmt->execute([$from, $to]);
        } else {
            // Employee: public events + private events where they are in visible_to
            $stmt = $pdo->prepare(
                "SELECT sys_id, event_date, title, description, visibility, created_at
                 FROM office_calendar_events
                 WHERE event_date BETWEEN ? AND ?
                   AND (visibility = 'public'
                        OR (visibility = 'private' AND JSON_CONTAINS(visible_to, JSON_QUOTE(?))))
                 ORDER BY event_date ASC, created_at ASC"
            );
            $stmt->execute([$from, $to, $myId]);
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode visible_to JSON
        foreach ($rows as &$row) {
            if (isset($row['visible_to']) && is_string($row['visible_to'])) {
                $row['visible_to'] = json_decode($row['visible_to'], true) ?? [];
            }
        }
        unset($row);

        // Group by date
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['event_date']][] = $row;
        }
        jsonOk(['data' => $rows, 'by_date' => $byDate, 'year' => $year]);
    }

    // ── ADD EVENT (admin only) ─────────────────────────────
    case 'add': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $date       = trim($body['date']        ?? '');
        $title      = trim($body['title']       ?? '');
        $desc       = trim($body['description'] ?? '');
        $visibility = trim($body['visibility']  ?? 'public');
        $visibleTo  = $body['visible_to'] ?? [];

        if (!$date)  jsonErr('date is required.');
        if (!$title) jsonErr('title is required.');
        if (!in_array($visibility, ['public','private'])) jsonErr('Invalid visibility.');
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt) jsonErr('Invalid date format.');

        $sysId = 'EVT-' . strtoupper(uniqid());
        $visJson = ($visibility === 'private') ? json_encode(array_values((array)$visibleTo)) : null;

        $pdo->prepare(
            "INSERT INTO office_calendar_events (sys_id, event_date, title, description, visibility, visible_to, created_by)
             VALUES (?,?,?,?,?,?,?)"
        )->execute([$sysId, $date, $title, $desc ?: null, $visibility, $visJson, $myId]);

        $row = $pdo->prepare("SELECT * FROM office_calendar_events WHERE sys_id=?");
        $row->execute([$sysId]);
        $evt = $row->fetch(PDO::FETCH_ASSOC);
        if ($evt && is_string($evt['visible_to'])) {
            $evt['visible_to'] = json_decode($evt['visible_to'], true) ?? [];
        }
        jsonOk(['message' => 'Event created.', 'event' => $evt]);
    }

    // ── UPDATE EVENT (admin only) ─────────────────────────
    case 'update': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $sysId      = trim($body['sys_id']       ?? '');
        $date       = trim($body['date']         ?? '');
        $title      = trim($body['title']        ?? '');
        $desc       = trim($body['description']  ?? '');
        $visibility = trim($body['visibility']   ?? '');
        $visibleTo  = $body['visible_to'] ?? null;

        if (!$sysId) jsonErr('sys_id is required.');

        $exist = $pdo->prepare("SELECT sys_id FROM office_calendar_events WHERE sys_id=?");
        $exist->execute([$sysId]);
        if (!$exist->fetch()) jsonErr('Event not found.', 404);

        $sets = [];
        $vals = [];
        if ($date)       { $sets[] = 'event_date=?';   $vals[] = $date; }
        if ($title)      { $sets[] = 'title=?';        $vals[] = $title; }
        if ($desc !== '') { $sets[] = 'description=?'; $vals[] = $desc ?: null; }
        if ($visibility) {
            if (!in_array($visibility, ['public','private'])) jsonErr('Invalid visibility.');
            $sets[] = 'visibility=?'; $vals[] = $visibility;
            if ($visibility === 'private' && $visibleTo !== null) {
                $sets[] = 'visible_to=?'; $vals[] = json_encode(array_values((array)$visibleTo));
            } elseif ($visibility === 'public') {
                $sets[] = 'visible_to=?'; $vals[] = null;
            }
        }
        if (!$sets) jsonErr('Nothing to update.');

        $vals[] = $sysId;
        $pdo->prepare("UPDATE office_calendar_events SET " . implode(',', $sets) . " WHERE sys_id=?")
            ->execute($vals);

        jsonOk(['message' => 'Event updated.']);
    }

    // ── DELETE EVENT (admin only) ─────────────────────────
    case 'delete': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $sysId = trim($body['sys_id'] ?? '');
        if (!$sysId) jsonErr('sys_id is required.');

        $exist = $pdo->prepare("SELECT sys_id FROM office_calendar_events WHERE sys_id=?");
        $exist->execute([$sysId]);
        if (!$exist->fetch()) jsonErr('Event not found.', 404);

        $pdo->prepare("DELETE FROM office_calendar_events WHERE sys_id=?")->execute([$sysId]);
        jsonOk(['message' => 'Event deleted.']);
    }

    default:
        jsonErr("Unknown action: $action");
}