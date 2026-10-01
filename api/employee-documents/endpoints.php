<?php
// ============================================================
// TravHub Gen-3 — Employee Documents API
// POST/GET /api/employee-documents/endpoints.php
// Body: { action, ...params } OR multipart for upload
//
// Employees can upload their own documents.
// HR can upload documents for any employee.
// Employees can only see their own documents.
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

$myId   = $_SESSION['user_id']   ?? '';
$myName = $_SESSION['user_name'] ?? '';
$isHR   = canAccess($pdo,'hrm_employee_view') || ($_SESSION['user_role']??'') === '0';

function jsonOk(array $d): void  { echo json_encode(['success'=>true]+$d); exit; }
function jsonErr(string $m, int $c=400): void {
    http_response_code($c);
    echo json_encode(['success'=>false,'message'=>$m]);
    exit;
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
if (!$action) {
    $raw    = file_get_contents('php://input');
    $body   = json_decode($raw, true) ?? [];
    $action = trim($body['action'] ?? '');
} else {
    $body = $_POST;
}

// ── Upload base dir ───────────────────────────────────────
define('EDOC_BASE', __DIR__ . '/../../uploads/employee-docs/');

switch ($action) {

    // ── LIST ──────────────────────────────────────────────
    case 'list': {
        $empId = $isHR ? trim($body['emp_id'] ?? $myId) : $myId;

        $stmt = $pdo->prepare(
            "SELECT sys_id, title, file_name, file_type, file_size, file_path,
                    is_hr_issued, uploaded_by_name, created_at
             FROM employee_documents
             WHERE employee_sys_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$empId]);
        jsonOk(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ── UPLOAD ────────────────────────────────────────────
    case 'upload': {
        // emp_id: self unless HR uploading for another employee
        $empId   = $isHR ? trim($_POST['emp_id'] ?? $myId) : $myId;
        $title   = trim($_POST['title']  ?? '');
        $hrIssued = $isHR && ($empId !== $myId) ? 1 : 0;

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            jsonErr('No file uploaded or upload error.');
        }

        $file      = $_FILES['file'];
        $origName  = basename($file['name']);
        $mime      = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
        $size      = (int)$file['size'];
        $ext       = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Allowed extensions
        $allowed = ['pdf','doc','docx','jpg','jpeg','png','xls','xlsx','txt'];
        if (!in_array($ext, $allowed)) jsonErr('File type not allowed.');
        if ($size > 10*1024*1024) jsonErr('File too large (max 10 MB).');

        // Store under uploads/employee-docs/{emp_sys_id}/
        $empDir = EDOC_BASE . $empId . '/';
        if (!is_dir($empDir)) mkdir($empDir, 0755, true);

        $stored = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
        $dest   = $empDir . $stored;

        if (!move_uploaded_file($file['tmp_name'], $dest)) jsonErr('Failed to save file.');

        if (!$title) $title = pathinfo($origName, PATHINFO_FILENAME);

        $sysId = 'EDOC-' . strtoupper(uniqid());
        $relPath = $empId . '/' . $stored;

        $pdo->prepare(
            "INSERT INTO employee_documents
                (sys_id, employee_sys_id, title, file_path, file_name, file_type, file_size, uploaded_by, uploaded_by_name, is_hr_issued)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        )->execute([$sysId, $empId, $title, $relPath, $origName, $mime, $size, $myId, $myName, $hrIssued]);

        jsonOk(['message'=>'Document uploaded.', 'sys_id'=>$sysId, 'file_name'=>$origName]);
    }

    // ── DELETE ────────────────────────────────────────────
    case 'delete': {
        $sysId = trim($body['sys_id'] ?? '');
        if (!$sysId) jsonErr('sys_id is required.');

        // Only HR or the uploader can delete
        $stmt = $pdo->prepare("SELECT * FROM employee_documents WHERE sys_id=?");
        $stmt->execute([$sysId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) jsonErr('Not found.', 404);

        if (!$isHR && $row['employee_sys_id'] !== $myId) jsonErr('Permission denied.', 403);

        // Delete file
        $fullPath = EDOC_BASE . $row['file_path'];
        if (file_exists($fullPath)) @unlink($fullPath);

        $pdo->prepare("DELETE FROM employee_documents WHERE sys_id=?")->execute([$sysId]);
        jsonOk(['message'=>'Deleted.']);
    }

    default:
        jsonErr("Unknown action: $action");
}