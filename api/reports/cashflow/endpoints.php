<?php
/**
 * api/reports/cashflow/endpoints.php
 *
 * Cash Flow Report
 * Source: ac_banking_stmts
 *
 * Overall:
 *   Cash In  = SUM(deposit)
 *   Cash Out = SUM(withdraw)
 *   Net Flow = Cash In - Cash Out
 *
 * Account-wise:
 *   Per account: deposit, withdraw, net, closing balance
 *
 * Actions:
 *   summary  → overall + account-wise (default)
 *   breakdown→ period-wise (daily/monthly)
 *   detail   → row-level statements
 *   filters  → account/method dropdowns
 *   export   → full data
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../server/db_connection.php';

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

/* ============================================================
   BUILD FILTERS
============================================================ */
function buildCashFilters(): array
{
    $where  = [];
    $params = [];

    if (!empty($_GET['date_from'])) {
        $where[]              = 'bs.date >= :date_from';
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[]            = 'bs.date <= :date_to';
        $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
    }
    if (!empty($_GET['account_id'])) {
        $where[]               = 'bs.ledger_db_id = :account_id';
        $params[':account_id'] = $_GET['account_id'];
    }
    if (!empty($_GET['transfer_method'])) {
        $where[]                    = 'bs.transfer_method = :method';
        $params[':method']          = $_GET['transfer_method'];
    }
    if (!empty($_GET['work_id'])) {
        // ac_banking_stmts এ work নেই directly, related_type দিয়ে filter
        // work_id filter — financial_entries JOIN দিয়ে করতে হবে
        // এখানে skip করি — detail view এ available
    }
    if (!empty($_GET['search'])) {
        $where[]           = '(bs.name LIKE :search OR bs.particular LIKE :search)';
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    // is_historical বাদ
    $where[] = 'bs.is_historical = 0';

    $sql = 'AND ' . implode(' AND ', $where);
    return [$sql, $params];
}

/* ============================================================
   SUMMARY — overall + account-wise
============================================================ */
function handleSummary(PDO $pdo, bool $isExport): void
{
    [$whereSql, $params] = buildCashFilters();

    // Overall
    $overallStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(bs.deposit),  0) AS total_in,
            COALESCE(SUM(bs.withdraw), 0) AS total_out,
            COALESCE(SUM(bs.deposit) - SUM(bs.withdraw), 0) AS net_flow
        FROM ac_banking_stmts bs
        WHERE 1=1 {$whereSql}
    ");
    $overallStmt->execute($params);
    $overall = $overallStmt->fetch(PDO::FETCH_ASSOC);

    // Account-wise
    $accStmt = $pdo->prepare("
        SELECT
            bs.ledger_db_id AS account_id,
            bs.name AS account_name,
            COALESCE(SUM(bs.deposit),  0) AS cash_in,
            COALESCE(SUM(bs.withdraw), 0) AS cash_out,
            COALESCE(SUM(bs.deposit) - SUM(bs.withdraw), 0) AS net_flow,
            MAX(bs.balance) AS closing_balance,
            COUNT(*) AS transaction_count
        FROM ac_banking_stmts bs
        WHERE 1=1 {$whereSql}
        GROUP BY bs.ledger_db_id, bs.name
        ORDER BY cash_in DESC
    ");
    $accStmt->execute($params);
    $accounts = $accStmt->fetchAll(PDO::FETCH_ASSOC);

    // Method-wise summary
    $methodStmt = $pdo->prepare("
        SELECT
            bs.transfer_method,
            COALESCE(SUM(bs.deposit),  0) AS cash_in,
            COALESCE(SUM(bs.withdraw), 0) AS cash_out,
            COALESCE(SUM(bs.deposit) - SUM(bs.withdraw), 0) AS net_flow,
            COUNT(*) AS count
        FROM ac_banking_stmts bs
        WHERE 1=1 {$whereSql}
        GROUP BY bs.transfer_method
        ORDER BY cash_in DESC
    ");
    $methodStmt->execute($params);
    $methods = $methodStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'overall'  => $overall,
        'accounts' => $accounts,
        'methods'  => $methods,
    ]);
}

/* ============================================================
   BREAKDOWN — period-wise
============================================================ */
function handleBreakdown(PDO $pdo): void
{
    [$whereSql, $params] = buildCashFilters();
    $period    = $_GET['period'] ?? 'monthly';
    $dateFormat= $period === 'daily' ? '%Y-%m-%d' : '%Y-%m';

    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(bs.date, '{$dateFormat}') AS period,
            COALESCE(SUM(bs.deposit),  0) AS cash_in,
            COALESCE(SUM(bs.withdraw), 0) AS cash_out,
            COALESCE(SUM(bs.deposit) - SUM(bs.withdraw), 0) AS net_flow,
            COUNT(*) AS transaction_count
        FROM ac_banking_stmts bs
        WHERE 1=1 {$whereSql}
        GROUP BY period
        ORDER BY period ASC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Account-wise breakdown per period
    $accPeriodStmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(bs.date, '{$dateFormat}') AS period,
            bs.ledger_db_id AS account_id,
            bs.name AS account_name,
            COALESCE(SUM(bs.deposit),  0) AS cash_in,
            COALESCE(SUM(bs.withdraw), 0) AS cash_out,
            COALESCE(SUM(bs.deposit) - SUM(bs.withdraw), 0) AS net_flow
        FROM ac_banking_stmts bs
        WHERE 1=1 {$whereSql}
        GROUP BY period, bs.ledger_db_id, bs.name
        ORDER BY period ASC, cash_in DESC
    ");
    $accPeriodStmt->execute($params);
    $accountBreakdown = $accPeriodStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'           => true,
        'period'            => $period,
        'rows'              => $rows,
        'account_breakdown' => $accountBreakdown,
    ]);
}

/* ============================================================
   DETAIL — row level statements
============================================================ */
function handleDetail(PDO $pdo): void
{
    [$whereSql, $params] = buildCashFilters();

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(500, max(1, (int)($_GET['per_page'] ?? 50)));
    $offset  = ($page - 1) * $perPage;

    $cntStmt = $pdo->prepare("
        SELECT COUNT(*) FROM ac_banking_stmts bs WHERE 1=1 {$whereSql}
    ");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    $params[':limit']  = $perPage;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare("
        SELECT
            bs.sys_id, bs.date,
            bs.ledger_db_id AS account_id,
            bs.name AS account_name,
            bs.particular,
            bs.deposit AS cash_in,
            bs.withdraw AS cash_out,
            bs.balance,
            bs.transfer_method,
            bs.related_type,
            bs.ref
        FROM ac_banking_stmts bs
        WHERE 1=1 {$whereSql}
        ORDER BY bs.date DESC, bs.id DESC
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

/* ============================================================
   FILTERS — dropdown data
============================================================ */
function handleFilters(PDO $pdo): void
{
    // Accounts
    $accounts = $pdo->query("
        SELECT sys_id AS id, acc_name AS name, balance
        FROM ac_banking
        ORDER BY acc_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Transfer methods
    $methods = $pdo->query("
        SELECT DISTINCT transfer_method AS method
        FROM ac_banking_stmts
        WHERE transfer_method IS NOT NULL AND transfer_method != ''
        ORDER BY transfer_method ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Works (from financial_entries — linked via ref)
    $works = $pdo->query("
        SELECT DISTINCT work_sys_id AS id, work_title AS name
        FROM financial_entries
        WHERE work_sys_id IS NOT NULL AND work_sys_id != ''
        ORDER BY work_title ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Tasks
    $tasks = $pdo->query("
        SELECT DISTINCT task_sys_id AS id, task_title AS name
        FROM financial_entries
        WHERE task_sys_id IS NOT NULL AND task_sys_id != ''
        ORDER BY task_title ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'accounts' => $accounts,
        'methods'  => $methods,
        'works'    => $works,
        'tasks'    => $tasks,
    ]);
}