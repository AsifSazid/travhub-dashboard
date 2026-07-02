<?php
// api/financial_entries/client-task-statement.php
// Changes:
//   - sys_id, qty_rate, is_invoiced, related_type column যোগ হয়েছে SELECT এ
//   - Server-side filter: type, related_type, is_invoiced support
//   - Response key: 'finStmts' (fin-entries.php এর সাথে consistent)
//     backward compat এর জন্য 'stmt' ও রাখা হয়েছে

require '../../server/db_connection.php';
header('Content-Type: application/json');

$clientId = $_GET['id']        ?? $_GET['client_id'] ?? '';
$taskId   = $_GET['task_id']   ?? '';

// Optional filters
$filterType        = $_GET['type']         ?? null;
$filterRelatedType = isset($_GET['related_type']) ? (int)$_GET['related_type'] : null;
$filterIsInvoiced  = isset($_GET['is_invoiced'])  ? (int)$_GET['is_invoiced']  : null;
$filterIsPaid      = isset($_GET['is_paid'])       ? (int)$_GET['is_paid']      : null;

if (empty($clientId) || empty($taskId)) {
    echo json_encode(['success' => false, 'message' => 'client_id and task_id are required']);
    exit;
}

try {
    $conditions = ["user_sys_id = :client_id", "task_sys_id = :task_id"];
    $params     = [':client_id' => $clientId, ':task_id' => $taskId];

    if ($filterType !== null && in_array($filterType, ['credit', 'debit'], true)) {
        $conditions[] = "type = :type";
        $params[':type'] = $filterType;
    }

    if ($filterRelatedType !== null) {
        $conditions[] = "related_type = :related_type";
        $params[':related_type'] = $filterRelatedType;
    }

    if ($filterIsInvoiced !== null) {
        $conditions[] = "is_invoiced = :is_invoiced";
        $params[':is_invoiced'] = $filterIsInvoiced;
    }

    if ($filterIsPaid !== null) {
        $conditions[] = "is_paid = :is_paid";
        $params[':is_paid'] = $filterIsPaid;
    }

    $where = implode(' AND ', $conditions);

    $stmt = $pdo->prepare("
        SELECT
            sys_id, purpose, amount, type,
            related_type, qty_rate,
            is_paid, is_partial, is_discounted, is_invoiced,
            date, ref
        FROM financial_entries
        WHERE {$where}
        ORDER BY date ASC, id ASC
    ");
    $stmt->execute($params);
    $finStmts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'finStmts' => $finStmts, // নতুন key — fin-entries.php এর সাথে consistent
        'stmt'     => $finStmts  // backward compat — পুরনো code যারা stmt ব্যবহার করছে
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}