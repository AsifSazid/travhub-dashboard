<?php
/**
 * api/reports/cashflow_v2/endpoints.php
 *
 * Cash Flow Report (v2). Source: ac_banking_stmts (unchanged schema -- the
 * double-entry migration didn't touch this table's structure, only added
 * more rows to it via the new pay/receive/refund-settle endpoints).
 *
 * The only v2 addition is joining financial_entries via
 * ac_banking_stmts.ref = financial_entries.sys_id to expose which
 * account_head/transaction_group_id each statement row belongs to, so the
 * report can show "this cash-out was a Vendor Payment" vs "this cash-out
 * was a Refund Paid to a client" instead of just a raw particular string.
 *
 * Actions:
 *   summary   -> overall + account-wise + method-wise (default)
 *   breakdown -> period-wise (daily/monthly)
 *   detail    -> row-level statements, with account_head context
 *   filters   -> account/method dropdowns
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
        $where[]           = 'bs.transfer_method = :method';
        $params[':method'] = $_GET['transfer_method'];
    }
    if (!empty($_GET['account_head'])) {
        $where[]                = 'fe.account_head = :account_head';
        $params[':account_head']= $_GET['account_head'];
    }
    if (!empty($_GET['search'])) {
        $where[]           = '(bs.name LIKE :search OR bs.particular LIKE :search)';
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    $where[] = 'bs.is_historical = 0';

    $sql = 'AND ' . implode(' AND ', $where);
    return [$sql, $params];
}

function handleSummary(PDO $pdo, bool $isExport): void
{
    [$whereSql, $params] = buildCashFilters();

    $overallStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(bs.deposit),  0) AS total_in,
            COALESCE(SUM(bs.withdraw), 0) AS total_out,
            COALESCE(SUM(bs.deposit) - SUM(bs.withdraw), 0) AS net_flow
        FROM ac_banking_stmts bs
        LEFT JOIN financial_entries fe ON fe.sys_id = bs.ref
        WHERE 1=1 {$whereSql}
    ");
    $overallStmt->execute($params);
    $overall = $overallStmt->fetch(PDO::FETCH_ASSOC);

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
        LEFT JOIN financial_entries fe ON fe.sys_id = bs.ref
        WHERE 1=1 {$whereSql}
        GROUP BY bs.ledger_db_id, bs.name
        ORDER BY cash_in DESC
    ");
    $accStmt->execute($params);
    $accounts = $accStmt->fetchAll(PDO::FETCH_ASSOC);

    // Account-head-wise summary (v2 addition) -- e.g. how much cash-out was
    // "vendor payment" vs "refund paid to client", how much cash-in was
    // "client receive" vs "vendor refund received"
    $headStmt = $pdo->prepare("
        SELECT
            COALESCE(fe.account_head, 'unlinked') AS source_account_head,
            COALESCE(SUM(bs.deposit),  0) AS cash_in,
            COALESCE(SUM(bs.withdraw), 0) AS cash_out,
            COUNT(*) AS count
        FROM ac_banking_stmts bs
        LEFT JOIN financial_entries fe ON fe.sys_id = bs.ref
        WHERE 1=1 {$whereSql}
        GROUP BY source_account_head
        ORDER BY cash_in DESC
    ");
    $headStmt->execute($params);
    $byAccountHead = $headStmt->fetchAll(PDO::FETCH_ASSOC);

    $methodStmt = $pdo->prepare("
        SELECT
            bs.transfer_method,
            COALESCE(SUM(bs.deposit),  0) AS cash_in,
            COALESCE(SUM(bs.withdraw), 0) AS cash_out,
            COALESCE(SUM(bs.deposit) - SUM(bs.withdraw), 0) AS net_flow,
            COUNT(*) AS count
        FROM ac_banking_stmts bs
        LEFT JOIN financial_entries fe ON fe.sys_id = bs.ref
        WHERE 1=1 {$whereSql}
        GROUP BY bs.transfer_method
        ORDER BY cash_in DESC
    ");
    $methodStmt->execute($params);
    $methods = $methodStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'         => true,
        'overall'         => $overall,
        'accounts'        => $accounts,
        'by_account_head' => $byAccountHead,
        'methods'         => $methods,
    ]);
}

function handleBreakdown(PDO $pdo): void
{
    [$whereSql, $params] = buildCashFilters();
    $period     = $_GET['period'] ?? 'monthly';
    $dateFormat = $period === 'daily' ? '%Y-%m-%d' : '%Y-%m';

    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(bs.date, '{$dateFormat}') AS period,
            COALESCE(SUM(bs.deposit),  0) AS cash_in,
            COALESCE(SUM(bs.withdraw), 0) AS cash_out,
            COALESCE(SUM(bs.deposit) - SUM(bs.withdraw), 0) AS net_flow,
            COUNT(*) AS transaction_count
        FROM ac_banking_stmts bs
        LEFT JOIN financial_entries fe ON fe.sys_id = bs.ref
        WHERE 1=1 {$whereSql}
        GROUP BY period
        ORDER BY period ASC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $accPeriodStmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(bs.date, '{$dateFormat}') AS period,
            bs.ledger_db_id AS account_id,
            bs.name AS account_name,
            COALESCE(SUM(bs.deposit),  0) AS cash_in,
            COALESCE(SUM(bs.withdraw), 0) AS cash_out,
            COALESCE(SUM(bs.deposit) - SUM(bs.withdraw), 0) AS net_flow
        FROM ac_banking_stmts bs
        LEFT JOIN financial_entries fe ON fe.sys_id = bs.ref
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

function handleDetail(PDO $pdo): void
{
    [$whereSql, $params] = buildCashFilters();

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(500, max(1, (int)($_GET['per_page'] ?? 50)));
    $offset  = ($page - 1) * $perPage;

    $cntStmt = $pdo->prepare("
        SELECT COUNT(*) FROM ac_banking_stmts bs
        LEFT JOIN financial_entries fe ON fe.sys_id = bs.ref
        WHERE 1=1 {$whereSql}
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
            bs.ref,
            fe.account_head AS source_account_head,
            fe.transaction_group_id,
            fe.user_type AS source_user_type,
            fe.user_name AS source_user_name
        FROM ac_banking_stmts bs
        LEFT JOIN financial_entries fe ON fe.sys_id = bs.ref
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

function handleFilters(PDO $pdo): void
{
    $accounts = $pdo->query("
        SELECT sys_id AS id, acc_name AS name, balance
        FROM ac_banking
        ORDER BY acc_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $methods = $pdo->query("
        SELECT DISTINCT transfer_method AS method
        FROM ac_banking_stmts
        WHERE transfer_method IS NOT NULL AND transfer_method != ''
        ORDER BY transfer_method ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $accountHeads = $pdo->query("
        SELECT DISTINCT account_head
        FROM financial_entries
        WHERE account_head IS NOT NULL
        ORDER BY account_head ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'       => true,
        'accounts'      => $accounts,
        'methods'       => $methods,
        'account_heads' => $accountHeads,
    ]);
}