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

try {
    // ── Shared WHERE clause ───────────────────────────────────
    $where  = "WHERE fe.account_head = 'ait_recoverable' AND fe.type = 'debit'";
    $params = [];

    if (!empty($_GET['date_from'])) { $where .= " AND DATE(fe.date) >= :df"; $params[':df'] = $_GET['date_from']; }
    if (!empty($_GET['date_to']))   { $where .= " AND DATE(fe.date) <= :dt"; $params[':dt'] = $_GET['date_to']; }
    if (!empty($_GET['vendor_id'])) { $where .= " AND fe.user_sys_id = :vi"; $params[':vi'] = $_GET['vendor_id']; }
    if (!empty($_GET['work_id']))   { $where .= " AND fe.work_sys_id = :wi"; $params[':wi'] = $_GET['work_id']; }

    if ($action === 'summary') {
        // Vendor-wise totals
        $stmt = $pdo->prepare("
            SELECT
                fe.user_sys_id  AS vendor_id,
                fe.user_name    AS vendor_name,
                COUNT(*)        AS entry_count,
                SUM(fe.amount)  AS total_ait
            FROM financial_entries fe
            {$where}
            GROUP BY fe.user_sys_id, fe.user_name
            ORDER BY total_ait DESC
        ");
        $stmt->execute($params);
        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success'=>true, 'vendors'=>$vendors]);

    } else {
        // Detail — paginated
        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = min(500, (int)($_GET['per_page'] ?? 50));
        $offset  = ($page - 1) * $perPage;

        // Total count + grand total
        $cntStmt = $pdo->prepare("SELECT COUNT(*), SUM(fe.amount) FROM financial_entries fe {$where}");
        $cntStmt->execute($params);
        [$total, $grandTotal] = $cntStmt->fetch(PDO::FETCH_NUM);

        $params[':limit']  = $perPage;
        $params[':offset'] = $offset;

        $stmt = $pdo->prepare("
            SELECT
                fe.sys_id, fe.date,
                fe.user_sys_id, fe.user_name,
                fe.work_sys_id, fe.work_title,
                fe.task_sys_id, fe.task_title,
                fe.purpose, fe.amount, fe.ref
            FROM financial_entries fe
            {$where}
            ORDER BY fe.date DESC, fe.id DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success'     => true,
            'rows'        => $rows,
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