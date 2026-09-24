<?php
/**
 * api/reports/sale_v2/endpoints.php
 *
 * Sale Report (v2). Source table: financial_entries
 * (account_head = 'sales', the revenue-recognition leg of a client sale).
 * Nets refund-declare reversals the same way purchase_v2 does for purchases.
 *
 * Actions (?action=...):
 *   list     -> paginated rows + summary (default)
 *   filters  -> client list + work list for dropdowns
 *   export   -> ALL matching rows (no pagination)
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../server/db_connection.php';
session_start();
require_once __DIR__ . '/../../../server/permissions.php';
requireFullAccountingAccess($pdo, true);

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'filters':
            handleFilters($pdo);
            break;
        case 'export':
            handleList($pdo, true);
            break;
        case 'list':
        default:
            handleList($pdo, false);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error in sale_v2 report endpoint.',
        'detail'  => $e->getMessage(),
    ]);
}

function handleFilters(PDO $pdo): void
{
    $clientStmt = $pdo->query("
        SELECT DISTINCT user_sys_id, user_name
        FROM financial_entries
        WHERE account_head = 'sales' AND user_sys_id IS NOT NULL
        ORDER BY user_name ASC
    ");
    $clients = $clientStmt->fetchAll(PDO::FETCH_ASSOC);

    $workStmt = $pdo->query("
        SELECT DISTINCT work_sys_id, work_title
        FROM financial_entries
        WHERE account_head = 'sales' AND work_sys_id IS NOT NULL
        ORDER BY work_title ASC
    ");
    $works = $workStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'clients' => $clients, 'works' => $works]);
}

function handleList(PDO $pdo, bool $isExport): void
{
    [$whereSql, $params] = buildFilters();

    $baseSql = "FROM financial_entries fe WHERE {$whereSql}";

    // Net sale = original revenue (credit) minus any refund reversal (debit)
    $sumStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total_count,
            COALESCE(SUM(CASE WHEN fe.type = 'credit' THEN fe.amount ELSE -fe.amount END), 0) AS total_amount
        {$baseSql}
    ");
    $sumStmt->execute($params);
    $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

    $orderSql = " ORDER BY fe.date DESC, fe.id DESC ";
    $selectCols = "
        fe.id, fe.uuid, fe.sys_id, fe.transaction_group_id, fe.user_sys_id, fe.user_name,
        fe.task_sys_id, fe.task_title, fe.work_sys_id, fe.work_title,
        fe.date, fe.purpose, fe.type, fe.amount, fe.ref,
        fe.is_paid, fe.is_partial, fe.is_discounted
    ";

    if ($isExport) {
        $stmt = $pdo->prepare("SELECT {$selectCols} {$baseSql} {$orderSql}");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'summary' => $summary, 'rows' => $rows]);
        return;
    }

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(500, max(1, (int)($_GET['per_page'] ?? 25)));
    $offset  = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("SELECT {$selectCols} {$baseSql} {$orderSql} LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'summary'  => $summary,
        'rows'     => $rows,
        'page'     => $page,
        'per_page' => $perPage,
        'total'    => (int)$summary['total_count'],
        'pages'    => (int)ceil($summary['total_count'] / $perPage),
    ]);
}

function buildFilters(): array
{
    $where  = ["fe.account_head = 'sales'"];
    $params = [];

    if (!empty($_GET['date_from'])) {
        $where[] = "fe.date >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = "fe.date <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }
    if (!empty($_GET['client']) && is_array($_GET['client'])) {
        $keys = [];
        foreach ($_GET['client'] as $i => $val) {
            $k = ":client{$i}";
            $keys[] = $k;
            $params[$k] = $val;
        }
        $where[] = "fe.user_sys_id IN (" . implode(',', $keys) . ")";
    }
    if (!empty($_GET['work']) && is_array($_GET['work'])) {
        $keys = [];
        foreach ($_GET['work'] as $i => $val) {
            $k = ":work{$i}";
            $keys[] = $k;
            $params[$k] = $val;
        }
        $where[] = "fe.work_sys_id IN (" . implode(',', $keys) . ")";
    }
    if (!empty($_GET['amount_min'])) {
        $where[] = "fe.amount >= :amount_min";
        $params[':amount_min'] = $_GET['amount_min'];
    }
    if (!empty($_GET['amount_max'])) {
        $where[] = "fe.amount <= :amount_max";
        $params[':amount_max'] = $_GET['amount_max'];
    }
    if (isset($_GET['is_paid']) && $_GET['is_paid'] !== '') {
        $where[] = "fe.is_paid = :is_paid";
        $params[':is_paid'] = (int)$_GET['is_paid'];
    }
    if (isset($_GET['is_discounted']) && $_GET['is_discounted'] !== '') {
        $where[] = "fe.is_discounted = :is_discounted";
        $params[':is_discounted'] = (int)$_GET['is_discounted'];
    }
    if (!empty($_GET['search'])) {
        $where[] = "(fe.purpose LIKE :search OR fe.ref LIKE :search OR fe.user_name LIKE :search OR fe.sys_id LIKE :search OR fe.work_title LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    return [implode(' AND ', $where), $params];
}