<?php
/**
 * api/reports/profit_v2/endpoints.php
 *
 * Profit / Loss Report (v2). Source: financial_entries, account_head-based.
 *
 * Formula:
 *   Revenue (net)    = SUM(credit, account_head='sales') - SUM(debit, account_head='sales')
 *                      (debit = refund reversal against a sale)
 *   COGS (net)       = SUM(debit,  account_head='purchase') - SUM(credit, account_head='purchase')
 *                      (credit = refund reversal against a purchase)
 *   Gross Profit     = Revenue - COGS
 *   Refund Charge Profit = SUM(debit, account_head='refund_charge', user_type='client')
 *                        - SUM(credit, account_head='refund_charge', user_type='vendor')
 *                      (client-side charge we keep, minus vendor-side charge we lose)
 *   Net Profit       = Gross Profit + Refund Charge Profit
 *
 * This replaces the old related_type=5 "discount" bucket -- discounts and
 * refund charges are handled as their own explicit account_head now rather
 * than one shared "type 5" flag.
 *
 * Actions:
 *   summary   -> overall P/L numbers (default)
 *   breakdown -> period-wise (daily/monthly) breakdown
 *   detail    -> row-level entries
 *   filters   -> client/vendor/work/task dropdowns
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

function buildProfitFilters(): array
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
    if (!empty($_GET['client_id'])) {
        $where[]              = "(fe.user_type = 'client' AND fe.user_sys_id = :client_id)";
        $params[':client_id'] = $_GET['client_id'];
    }
    if (!empty($_GET['vendor_id'])) {
        $where[]              = "(fe.user_type = 'vendor' AND fe.user_sys_id = :vendor_id)";
        $params[':vendor_id'] = $_GET['vendor_id'];
    }
    if (!empty($_GET['work_id'])) {
        $where[]            = 'fe.work_sys_id = :work_id';
        $params[':work_id'] = $_GET['work_id'];
    }
    if (!empty($_GET['task_id'])) {
        $where[]            = 'fe.task_sys_id = :task_id';
        $params[':task_id'] = $_GET['task_id'];
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
    [$whereSql, $params] = buildProfitFilters();

    // Revenue: net of any refund reversal against the sale
    $revStmt = $pdo->prepare("
        SELECT COALESCE(SUM(CASE WHEN fe.type='credit' THEN fe.amount ELSE -fe.amount END), 0)
        FROM financial_entries fe
        WHERE fe.account_head = 'sales' {$whereSql}
    ");
    $revStmt->execute($params);
    $revenue = (float)$revStmt->fetchColumn();

    // COGS: net of any refund reversal against the purchase
    $cogsStmt = $pdo->prepare("
        SELECT COALESCE(SUM(CASE WHEN fe.type='debit' THEN fe.amount ELSE -fe.amount END), 0)
        FROM financial_entries fe
        WHERE fe.account_head = 'purchase' {$whereSql}
    ");
    $cogsStmt->execute($params);
    $cogs = (float)$cogsStmt->fetchColumn();

    // Refund charge profit: client-side charge kept (debit) minus vendor-side charge lost (credit)
    $rcClientStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0) FROM financial_entries fe
        WHERE fe.account_head = 'refund_charge' AND fe.user_type = 'client' AND fe.type = 'debit' {$whereSql}
    ");
    $rcClientStmt->execute($params);
    $refundChargeClient = (float)$rcClientStmt->fetchColumn();

    $rcVendorStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0) FROM financial_entries fe
        WHERE fe.account_head = 'refund_charge' AND fe.user_type = 'vendor' AND fe.type = 'credit' {$whereSql}
    ");
    $rcVendorStmt->execute($params);
    $refundChargeVendor = (float)$rcVendorStmt->fetchColumn();

    $refundChargeProfit = $refundChargeClient - $refundChargeVendor;
    $grossProfit = $revenue - $cogs;

    // Office operating expenses (rent, utility, marketing, etc.)
    $expStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0) FROM financial_entries fe
        WHERE fe.account_head = 'expense' {$whereSql}
    ");
    $expStmt->execute($params);
    $totalExpense = (float)$expStmt->fetchColumn();

    // Payroll expense (salary/bonus/overtime, once generate-salary.php is
    // wired to write financial_entries rows -- see Phase 4 integration)
    $payrollStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0) FROM financial_entries fe
        WHERE fe.account_head = 'payroll_expense' {$whereSql}
    ");
    $payrollStmt->execute($params);
    $totalPayrollExpense = (float)$payrollStmt->fetchColumn();

    $netProfit = $grossProfit + $refundChargeProfit - $totalExpense - $totalPayrollExpense;

    // Client-wise breakdown
    $clientStmt = $pdo->prepare("
        SELECT
            fe.user_sys_id,
            fe.user_name,
            SUM(CASE WHEN fe.account_head = 'sales' AND fe.type='credit' THEN fe.amount
                     WHEN fe.account_head = 'sales' AND fe.type='debit'  THEN -fe.amount ELSE 0 END) AS sale,
            SUM(CASE WHEN fe.account_head = 'bank_account' AND fe.type='debit' THEN fe.amount ELSE 0 END) AS receive,
            SUM(CASE WHEN fe.account_head = 'refund_charge' AND fe.type='debit' THEN fe.amount ELSE 0 END) AS refund_charge
        FROM financial_entries fe
        WHERE fe.user_type = 'client'
          AND fe.account_head IN ('sales','bank_account','refund_charge')
          {$whereSql}
        GROUP BY fe.user_sys_id, fe.user_name
        ORDER BY sale DESC
    ");
    $clientStmt->execute($params);
    $clientRows = $clientStmt->fetchAll(PDO::FETCH_ASSOC);

    // Vendor-wise breakdown
    $vendorStmt = $pdo->prepare("
        SELECT
            fe.user_sys_id,
            fe.user_name,
            SUM(CASE WHEN fe.account_head = 'purchase' AND fe.type='debit'  THEN fe.amount
                     WHEN fe.account_head = 'purchase' AND fe.type='credit' THEN -fe.amount ELSE 0 END) AS purchase,
            SUM(CASE WHEN fe.account_head = 'accounts_payable' AND fe.type='debit' THEN fe.amount ELSE 0 END) AS payment,
            SUM(CASE WHEN fe.account_head = 'refund_charge' AND fe.type='credit' THEN fe.amount ELSE 0 END) AS refund_charge
        FROM financial_entries fe
        WHERE fe.user_type = 'vendor'
          AND fe.account_head IN ('purchase','accounts_payable','refund_charge')
          {$whereSql}
        GROUP BY fe.user_sys_id, fe.user_name
        ORDER BY purchase DESC
    ");
    $vendorStmt->execute($params);
    $vendorRows = $vendorStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'summary' => [
            'revenue'               => $revenue,
            'cogs'                  => $cogs,
            'gross_profit'          => $grossProfit,
            'refund_charge_client'  => $refundChargeClient,
            'refund_charge_vendor'  => $refundChargeVendor,
            'refund_charge_profit'  => $refundChargeProfit,
            'total_expense'         => $totalExpense,
            'total_payroll_expense' => $totalPayrollExpense,
            'net_profit'            => $netProfit,
            'margin_pct'            => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0,
        ],
        'clients' => $clientRows,
        'vendors' => $vendorRows,
    ]);
}

function handleBreakdown(PDO $pdo): void
{
    [$whereSql, $params] = buildProfitFilters();
    $period = $_GET['period'] ?? 'monthly';
    $dateFormat = $period === 'daily' ? '%Y-%m-%d' : '%Y-%m';

    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(fe.date, '{$dateFormat}') AS period,
            SUM(CASE WHEN fe.account_head='sales' AND fe.type='credit' THEN fe.amount
                     WHEN fe.account_head='sales' AND fe.type='debit'  THEN -fe.amount ELSE 0 END) AS revenue,
            SUM(CASE WHEN fe.account_head='purchase' AND fe.type='debit'  THEN fe.amount
                     WHEN fe.account_head='purchase' AND fe.type='credit' THEN -fe.amount ELSE 0 END) AS cogs,
            SUM(CASE WHEN fe.account_head='refund_charge' AND fe.user_type='client' AND fe.type='debit'  THEN fe.amount
                     WHEN fe.account_head='refund_charge' AND fe.user_type='vendor' AND fe.type='credit' THEN -fe.amount ELSE 0 END) AS refund_charge_profit,
            SUM(CASE WHEN fe.account_head='expense' THEN fe.amount ELSE 0 END) AS expense,
            SUM(CASE WHEN fe.account_head='payroll_expense' THEN fe.amount ELSE 0 END) AS payroll_expense
        FROM financial_entries fe
        WHERE fe.account_head IN ('sales','purchase','refund_charge','expense','payroll_expense')
          {$whereSql}
        GROUP BY period
        ORDER BY period ASC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['net_profit'] = round(
            (float)$r['revenue'] - (float)$r['cogs'] + (float)$r['refund_charge_profit']
            - (float)$r['expense'] - (float)$r['payroll_expense'],
            2
        );
    }

    echo json_encode(['success'=>true,'period'=>$period,'rows'=>$rows]);
}

function handleDetail(PDO $pdo): void
{
    [$whereSql, $params] = buildProfitFilters();

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(500, max(1, (int)($_GET['per_page'] ?? 50)));
    $offset  = ($page - 1) * $perPage;

    $cntStmt = $pdo->prepare("
        SELECT COUNT(*) FROM financial_entries fe
        WHERE fe.account_head IN ('sales','purchase','refund_charge','expense','payroll_expense')
          {$whereSql}
    ");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    $params[':limit']  = $perPage;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare("
        SELECT
            fe.sys_id, fe.transaction_group_id, fe.date, fe.user_type, fe.user_sys_id, fe.user_name,
            fe.purpose, fe.type, fe.amount, fe.account_head,
            fe.work_sys_id, fe.work_title, fe.task_sys_id, fe.task_title,
            CASE fe.account_head
                WHEN 'sales' THEN 'Sale'
                WHEN 'purchase' THEN 'Purchase'
                WHEN 'refund_charge' THEN 'Refund Charge'
                WHEN 'expense' THEN 'Office Expense'
                WHEN 'payroll_expense' THEN 'Payroll'
            END AS entry_type
        FROM financial_entries fe
        WHERE fe.account_head IN ('sales','purchase','refund_charge','expense','payroll_expense')
          {$whereSql}
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
    $clients = $pdo->query("
        SELECT DISTINCT user_sys_id AS id, user_name AS name
        FROM financial_entries
        WHERE user_type = 'client' AND account_head = 'sales'
        ORDER BY user_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $vendors = $pdo->query("
        SELECT DISTINCT user_sys_id AS id, user_name AS name
        FROM financial_entries
        WHERE user_type = 'vendor' AND account_head = 'purchase'
        ORDER BY user_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $works = $pdo->query("
        SELECT DISTINCT work_sys_id AS id, work_title AS name
        FROM financial_entries
        WHERE work_sys_id IS NOT NULL AND work_sys_id != '' AND account_head IS NOT NULL
        ORDER BY work_title ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $tasks = $pdo->query("
        SELECT DISTINCT task_sys_id AS id, task_title AS name
        FROM financial_entries
        WHERE task_sys_id IS NOT NULL AND task_sys_id != '' AND account_head IS NOT NULL
        ORDER BY task_title ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'clients' => $clients,
        'vendors' => $vendors,
        'works'   => $works,
        'tasks'   => $tasks,
    ]);
}