<?php
// ============================================================
// TravHub Gen-3 — Office Holidays API
// POST /api/holidays/endpoints.php
// Body: { action, ...params }
//
// list   — public; returns holidays for a given year
// add    — HR only
// update — HR only
// delete — HR only
// ============================================================

session_start();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../server/db_connection.php';
require_once '../../server/permissions.php';

if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthenticated']);
    exit;
}

$myId = $_SESSION['user_id'] ?? '';
$isHR = canAccess($pdo,'hrm_employee_view') || ($_SESSION['user_role']??'') === '0';

function jsonOk(array $d): void  { echo json_encode(['success'=>true]+$d); exit; }
function jsonErr(string $m, int $c=400): void {
    http_response_code($c);
    echo json_encode(['success'=>false,'message'=>$m]);
    exit;
}

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = trim($body['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '');

switch ($action) {

    // ── LIST ──────────────────────────────────────────────
    case 'list': {
        $year = (int)($body['year'] ?? $_GET['year'] ?? date('Y'));
        $stmt = $pdo->prepare(
            "SELECT sys_id, holiday_date, title, type, year
             FROM office_holidays
             WHERE year = ?
             ORDER BY holiday_date ASC"
        );
        $stmt->execute([$year]);
        jsonOk(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'year' => $year]);
    }

    // ── ADD (HR only) ─────────────────────────────────────
    case 'add': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $date  = trim($body['holiday_date'] ?? '');
        $title = trim($body['title']        ?? '');
        $type  = trim($body['type']         ?? 'public_holiday');

        if (!$date || !$title) jsonErr('holiday_date and title are required.');

        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt) jsonErr('Invalid date format (YYYY-MM-DD).');

        $allowedTypes = ['public_holiday','office_closed','optional'];
        if (!in_array($type, $allowedTypes)) $type = 'public_holiday';

        $year  = (int)$dt->format('Y');
        $sysId = 'HOL-' . strtoupper(uniqid());

        try {
            $pdo->prepare(
                "INSERT INTO office_holidays (sys_id, holiday_date, title, type, year, created_by)
                 VALUES (?,?,?,?,?,?)"
            )->execute([$sysId, $date, $title, $type, $year, $myId]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') jsonErr('A holiday already exists on this date.');
            throw $e;
        }

        jsonOk(['message' => 'Holiday added.', 'sys_id' => $sysId]);
    }

    // ── UPDATE (HR only) ──────────────────────────────────
    case 'update': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $sysId = trim($body['sys_id']       ?? '');
        $date  = trim($body['holiday_date'] ?? '');
        $title = trim($body['title']        ?? '');
        $type  = trim($body['type']         ?? '');

        if (!$sysId) jsonErr('sys_id required.');

        $stmt = $pdo->prepare("SELECT * FROM office_holidays WHERE sys_id=?");
        $stmt->execute([$sysId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) jsonErr('Not found.', 404);

        $newDate  = $date  ?: $row['holiday_date'];
        $newTitle = $title ?: $row['title'];
        $newType  = in_array($type, ['public_holiday','office_closed','optional']) ? $type : $row['type'];
        $dt = DateTime::createFromFormat('Y-m-d', $newDate);
        if (!$dt) jsonErr('Invalid date format.');
        $newYear = (int)$dt->format('Y');

        try {
            $pdo->prepare(
                "UPDATE office_holidays
                 SET holiday_date=?, title=?, type=?, year=?
                 WHERE sys_id=?"
            )->execute([$newDate, $newTitle, $newType, $newYear, $sysId]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') jsonErr('Another holiday already exists on this date.');
            throw $e;
        }

        jsonOk(['message' => 'Holiday updated.']);
    }

    // ── DELETE (HR only) ──────────────────────────────────
    case 'delete': {
        if (!$isHR) jsonErr('Permission denied.', 403);

        $sysId = trim($body['sys_id'] ?? '');
        if (!$sysId) jsonErr('sys_id required.');

        $stmt = $pdo->prepare("SELECT sys_id FROM office_holidays WHERE sys_id=?");
        $stmt->execute([$sysId]);
        if (!$stmt->fetch()) jsonErr('Not found.', 404);

        $pdo->prepare("DELETE FROM office_holidays WHERE sys_id=?")->execute([$sysId]);
        jsonOk(['message' => 'Holiday deleted.']);
    }

    default:
        jsonErr("Unknown action: $action");
}