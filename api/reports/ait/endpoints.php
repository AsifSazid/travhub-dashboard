<?php
/**
 * FILE PATH: /api/reports/ait/endpoints.php
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../server/db_connection.php';
session_start();
require_once __DIR__ . '/../../../server/permissions.php';
requireFullAccountingAccess($pdo, true);

$action = $_GET['action'] ?? 'summary';

// ── File upload handler ───────────────────────────────────────
if ($action === 'upload_slip') {
    $entrySysId = $_POST['entry_sys_id'] ?? '';
    if (!$entrySysId || empty($_FILES['slip'])) {
        echo json_encode(['success'=>false,'message'=>'Missing entry_sys_id or file']); exit;
    }
    $uploadDir = __DIR__ . '/../../../uploads/ait-slips/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext      = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
    $fileName = 'AIT-' . $entrySysId . '-' . time() . '.' . $ext;
    $filePath = $uploadDir . $fileName;
    if (!move_uploaded_file($_FILES['slip']['tmp_name'], $filePath)) {
        echo json_encode(['success'=>false,'message'=>'Upload failed']); exit;
    }
    $webPath = 'uploads/ait-slips/' . $fileName;
    $pdo->prepare("UPDATE financial_entries SET ait_status='recovered', ait_slip_file=? WHERE sys_id=?")
        ->execute([$webPath, $entrySysId]);
    echo json_encode(['success'=>true,'file'=>$webPath]); exit;
}

// ── Claim application ─────────────────────────────────────────
if ($action === 'generate_claim') {
    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $ids     = $input['entry_ids'] ?? [];
    if (empty($ids)) { echo json_encode(['success'=>false,'message'=>'No entries selected']); exit; }
    $ph      = implode(',', array_fill(0, count($ids), '?'));
    $stmt    = $pdo->prepare("
        SELECT fe.sys_id, fe.date, fe.user_name, fe.amount, fe.purpose,
               fe.task_sys_id, fe.work_title, fe.ait_status, fe.ait_slip_file,
               (SELECT JSON_UNQUOTE(JSON_EXTRACT(at.at_bookings, '$[0].pnr'))
                FROM air_tickets at
                INNER JOIN tasks t ON t.work_sys_id COLLATE utf8mb4_unicode_ci = at.work_sys_id COLLATE utf8mb4_unicode_ci
                WHERE t.sys_id COLLATE utf8mb4_unicode_ci = fe.task_sys_id COLLATE utf8mb4_unicode_ci
                LIMIT 1) AS pnr
        FROM financial_entries fe
        WHERE fe.sys_id IN ($ph)
        AND fe.account_head = 'ait_recoverable'
        ORDER BY fe.date ASC
    ");
    $stmt->execute($ids);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $vendorName = $entries[0]['user_name'] ?? '';
    $totalAit   = array_sum(array_column($entries, 'amount'));
    echo json_encode(['success'=>true,'entries'=>$entries,'vendor_name'=>$vendorName,'total_ait'=>$totalAit]);
    exit;
}

// ── Shared WHERE ──────────────────────────────────────────────
$where  = "WHERE fe.account_head = 'ait_recoverable' AND fe.type = 'debit'";
$params = [];
if (!empty($_GET['date_from'])) { $where .= " AND DATE(fe.date) >= :df"; $params[':df'] = $_GET['date_from']; }
if (!empty($_GET['date_to']))   { $where .= " AND DATE(fe.date) <= :dt"; $params[':dt'] = $_GET['date_to']; }
if (!empty($_GET['vendor_id'])) { $where .= " AND fe.user_sys_id = :vi"; $params[':vi'] = $_GET['vendor_id']; }
if (!empty($_GET['work_id']))   { $where .= " AND fe.work_sys_id = :wi"; $params[':wi'] = $_GET['work_id']; }
if (!empty($_GET['status']))    { $where .= " AND fe.ait_status = :st";  $params[':st'] = $_GET['status']; }

try {
    if ($action === 'summary') {
        $stmt = $pdo->prepare("
            SELECT fe.user_sys_id AS vendor_id, fe.user_name AS vendor_name,
                   COUNT(*) AS entry_count, SUM(fe.amount) AS total_ait,
                   SUM(CASE WHEN fe.ait_status='recovered' THEN fe.amount ELSE 0 END) AS recovered,
                   SUM(CASE WHEN fe.ait_status='recoverable' OR fe.ait_status IS NULL THEN fe.amount ELSE 0 END) AS recoverable
            FROM financial_entries fe
            {$where}
            GROUP BY fe.user_sys_id, fe.user_name
            ORDER BY total_ait DESC
        ");
        $stmt->execute($params);
        echo json_encode(['success'=>true,'vendors'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } else {
        // Detail with PNR
        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = min(500, (int)($_GET['per_page'] ?? 50));
        $offset  = ($page - 1) * $perPage;

        $cntStmt = $pdo->prepare("SELECT COUNT(*), SUM(fe.amount) FROM financial_entries fe {$where}");
        $cntStmt->execute($params);
        [$total, $grandTotal] = $cntStmt->fetch(PDO::FETCH_NUM);

        $params[':limit']  = $perPage;
        $params[':offset'] = $offset;

        $stmt = $pdo->prepare("
            SELECT fe.sys_id, fe.date, fe.user_sys_id, fe.user_name,
                   fe.work_sys_id, fe.work_title, fe.task_sys_id, fe.task_title,
                   fe.purpose, fe.amount, fe.ait_status, fe.ait_slip_file,
                   (SELECT JSON_UNQUOTE(JSON_EXTRACT(at.at_bookings, '$[0].pnr'))
                    FROM air_tickets at
                    INNER JOIN tasks t ON t.work_sys_id COLLATE utf8mb4_unicode_ci = at.work_sys_id COLLATE utf8mb4_unicode_ci
                    WHERE t.sys_id COLLATE utf8mb4_unicode_ci = fe.task_sys_id COLLATE utf8mb4_unicode_ci
                    LIMIT 1) AS pnr
            FROM financial_entries fe
            {$where}
            ORDER BY fe.date DESC, fe.id DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->execute($params);
        echo json_encode([
            'success'     => true,
            'rows'        => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'grand_total' => (float)$grandTotal,
            'total'       => (int)$total,
            'page'        => $page,
            'per_page'    => $perPage,
            'pages'       => (int)ceil($total / $perPage),
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}