<?php
/**
 * api/reports/expense_v2/endpoints.php
 *
 * Office Expense Report. Source: financial_entries, account_head='expense'.
 * Each row's user_sys_id/user_name is the classification (Expense-category)
 * ac_banking account (e.g. "Office Rent", "Utility Bill"), not a vendor or
 * client -- this report groups by that classification to show which
 * expense category money is going to.
 *
 * Actions:
 *   summary   -> total + category-wise breakdown (default)
 *   breakdown -> period-wise (daily/monthly)
 *   detail    -> row-level entries
 *   filters   -> expense-category account dropdown
 *   export    -> full data
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../server/db_connection.php';
session_start();
require_once __DIR__ . '/../../../server/permissions.php';
requireFullAccountingAccess($pdo, true);

$action = $_GET['action'] ?? 'summary';

try {
    switch ($action) {
        case 'breakdown': handleBreakdown($pdo); break;
        case 'detail':    handleDetail($pdo);    break;
        case 'filters':   handleFilters($pdo);   break;
        case 'export':    handleSummary($pdo, true); break;
        case 'summary':
        default:          handleSummary($pdo, false); break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

function buildExpenseFilters(): array
{
    $where  = [];
    $params = [];

    if (!empty($_GET['date_from'])) {
        $where[]              = 'fe.date >= :date_from';
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[]            = 'fe.date <= :date_to';
        $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
    }
    if (!empty($_GET['expense_account_id'])) {
        $where[]                     = 'fe.user_sys_id = :expense_account_id';
        $params[':expense_account_id'] = $_GET['expense_account_id'];
    }
    if (!empty($_GET['amount_min'])) {
        $where[]              = 'fe.amount >= :amount_min';
        $params[':amount_min'] = $_GET['amount_min'];
    }
    if (!empty($_GET['amount_max'])) {
        $where[]              = 'fe.amount <= :amount_max';
        $params[':amount_max'] = $_GET['amount_max'];
    }
    if (!empty($_GET['search'])) {
        $where[]           = '(fe.user_name LIKE :search OR fe.purpose LIKE :search)';
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sql = empty($where) ? '' : 'AND ' . implode(' AND ', $where);
    return [$sql, $params];
}

function handleSummary(PDO $pdo, bool $isExport): void
{
    [$whereSql, $params] = buildExpenseFilters();

    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0) AS total, COUNT(*) AS entry_count
        FROM financial_entries fe
        WHERE fe.account_head = 'expense' {$whereSql}
    ");
    $totalStmt->execute($params);
    $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

    $byCategoryStmt = $pdo->prepare("
        SELECT fe.user_sys_id AS account_id, fe.user_name AS account_name,
               COALESCE(SUM(fe.amount), 0) AS total, COUNT(*) AS entry_count
        FROM financial_entries fe
        WHERE fe.account_head = 'expense' {$whereSql}
        GROUP BY fe.user_sys_id, fe.user_name
        ORDER BY total DESC
    ");
    $byCategoryStmt->execute($params);
    $byCategory = $byCategoryStmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [
        'success' => true,
        'summary' => [
            'total_expense' => (float)$totals['total'],
            'entry_count'   => (int)$totals['entry_count'],
        ],
        'by_category' => $byCategory,
    ];

    echo json_encode($result);
}

function handleBreakdown(PDO $pdo): void
{
    [$whereSql, $params] = buildExpenseFilters();
    $period     = $_GET['period'] ?? 'monthly';
    $dateFormat = $period === 'daily' ? '%Y-%m-%d' : '%Y-%m';

    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(fe.date, '{$dateFormat}') AS period,
            COALESCE(SUM(fe.amount), 0) AS total,
            COUNT(*) AS entry_count
        FROM financial_entries fe
        WHERE fe.account_head = 'expense' {$whereSql}
        GROUP BY period
        ORDER BY period ASC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true,'period'=>$period,'rows'=>$rows]);
}

function handleDetail(PDO $pdo): void
{
    [$whereSql, $params] = buildExpenseFilters();

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(500, max(1, (int)($_GET['per_page'] ?? 50)));
    $offset  = ($page - 1) * $perPage;

    $cntStmt = $pdo->prepare("
        SELECT COUNT(*) FROM financial_entries fe
        WHERE fe.account_head = 'expense' {$whereSql}
    ");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    $params[':limit']  = $perPage;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare("
        SELECT
            fe.sys_id, fe.transaction_group_id, fe.date,
            fe.user_sys_id AS expense_account_id, fe.user_name AS expense_category,
            fe.purpose, fe.amount, fe.ref
        FROM financial_entries fe
        WHERE fe.account_head = 'expense' {$whereSql}
        ORDER BY fe.date DESC, fe.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'rows'     => $rows,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int)ceil($total / $perPage),
    ]);
}

function handleFilters(PDO $pdo): void
{
    $accounts = $pdo->query("
        SELECT DISTINCT user_sys_id AS id, user_name AS name
        FROM financial_entries
        WHERE account_head = 'expense'
        ORDER BY user_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'expense_accounts' => $accounts]);
}