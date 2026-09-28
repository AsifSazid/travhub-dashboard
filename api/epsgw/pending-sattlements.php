<?php
// PATH: /api/epsgw/pending-settlements.php
// (epsgw = EPS Gateway)
//
// Lists gateway_payments awaiting manual settlement (status =
// 'pending_settlement'), plus recently settled ones for reference. Feeds
// pages/pending-gateway-settlements.php, where an admin confirms the EPS
// payout has actually landed in a bank account and calls settle.php.
//
// GET ?status=pending_settlement|settled|all   (default: pending_settlement)

session_start();
require_once __DIR__ . '/../../server/db_connection.php';
require_once __DIR__ . '/../../server/permissions.php';
requireFullAccountingAccess($pdo, true);
header('Content-Type: application/json');

$status = $_GET['status'] ?? 'pending_settlement';
if (!in_array($status, ['pending_settlement', 'settled', 'all'], true)) {
    $status = 'pending_settlement';
}

try {
    $where  = $status === 'all' ? "gp.status IN ('pending_settlement', 'settled')" : "gp.status = :status";
    $params = $status === 'all' ? [] : [':status' => $status];

    $stmt = $pdo->prepare("
        SELECT gp.sys_id, gp.invoice_sys_id, gp.client_sys_id, gp.amount, gp.status,
               gp.merchant_transaction_id, gp.eps_transaction_id,
               gp.confirmed_at, gp.settled_at, gp.settled_by,
               c.title AS client_name
        FROM gateway_payments gp
        LEFT JOIN clients c ON c.sys_id = gp.client_sys_id
        WHERE {$where}
        ORDER BY COALESCE(gp.settled_at, gp.confirmed_at) DESC
        LIMIT 200
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = $pdo->query("
        SELECT
            SUM(CASE WHEN status = 'pending_settlement' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'pending_settlement' THEN amount ELSE 0 END) AS pending_amount,
            SUM(CASE WHEN status = 'settled' THEN 1 ELSE 0 END) AS settled_count
        FROM gateway_payments
    ")->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'summary' => $summary, 'rows' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}