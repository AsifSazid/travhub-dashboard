<?php
/**
 * api/reports/profit/endpoints.php
 *
 * Profit / Loss Report
 * Source: financial_entries
 *
 * Formula:
 *   Revenue     = SUM(rt=1, type=debit, user_type=client)   — Sale
 *   COGS        = SUM(rt=2, type=credit, user_type=vendor)  — Purchase
 *   Gross Profit= Revenue - COGS
 *   Discount    = SUM(rt=5)                                  — Discount given
 *   Net Profit  = Gross Profit - Discount
 *   (rt=6 Advance, rt=7 Baksheesh বাদ)
 *
 * Actions:
 *   summary  → overall P/L numbers (default)
 *   breakdown→ period-wise (daily/monthly) breakdown
 *   detail   → row-level entries
 *   filters  → client/vendor/work/task dropdowns
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

/* ============================================================
   SUMMARY — overall P/L
============================================================ */
function handleSummary(PDO $pdo, bool $isExport): void
{
    [$whereSql, $params] = buildProfitFilters();

    // Revenue (Sale rt=1, client debit)
    $revStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0)
        FROM financial_entries fe
        WHERE fe.related_type = 1
          AND fe.type = 'debit'
          AND fe.user_type = 'client'
          {$whereSql}
    ");
    $revStmt->execute($params);
    $revenue = (float)$revStmt->fetchColumn();

    // COGS (Purchase rt=2, vendor credit)
    $cogsStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0)
        FROM financial_entries fe
        WHERE fe.related_type = 2
          AND fe.type = 'credit'
          AND fe.user_type = 'vendor'
          {$whereSql}
    ");
    $cogsStmt->execute($params);
    $cogs = (float)$cogsStmt->fetchColumn();

    // Discount (rt=5)
    $discStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0)
        FROM financial_entries fe
        WHERE fe.related_type = 5
          {$whereSql}
    ");
    $discStmt->execute($params);
    $discount = (float)$discStmt->fetchColumn();

    $grossProfit = $revenue - $cogs;
    $netProfit   = $grossProfit - $discount;

    // Client-wise breakdown
    $clientStmt = $pdo->prepare("
        SELECT
            fe.user_sys_id,
            fe.user_name,
            SUM(CASE WHEN fe.related_type = 1 THEN fe.amount ELSE 0 END) AS sale,
            SUM(CASE WHEN fe.related_type = 3 THEN fe.amount ELSE 0 END) AS receive,
            SUM(CASE WHEN fe.related_type = 5 AND fe.user_type='client' THEN fe.amount ELSE 0 END) AS discount
        FROM financial_entries fe
        WHERE fe.user_type = 'client'
          AND fe.related_type IN (1,3,5)
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
            SUM(CASE WHEN fe.related_type = 2 THEN fe.amount ELSE 0 END) AS purchase,
            SUM(CASE WHEN fe.related_type = 4 THEN fe.amount ELSE 0 END) AS payment,
            SUM(CASE WHEN fe.related_type = 5 AND fe.user_type='vendor' THEN fe.amount ELSE 0 END) AS discount
        FROM financial_entries fe
        WHERE fe.user_type = 'vendor'
          AND fe.related_type IN (2,4,5)
          {$whereSql}
        GROUP BY fe.user_sys_id, fe.user_name
        ORDER BY purchase DESC
    ");
    $vendorStmt->execute($params);
    $vendorRows = $vendorStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'summary' => [
            'revenue'      => $revenue,
            'cogs'         => $cogs,
            'gross_profit' => $grossProfit,
            'discount'     => $discount,
            'net_profit'   => $netProfit,
            'margin_pct'   => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0,
        ],
        'clients' => $clientRows,
        'vendors' => $vendorRows,
    ]);
}

/* ============================================================
   BREAKDOWN — period-wise (daily | monthly)
============================================================ */
function handleBreakdown(PDO $pdo): void
{
    [$whereSql, $params] = buildProfitFilters();
    $period = $_GET['period'] ?? 'monthly'; // 'daily' | 'monthly'

    $dateFormat = $period === 'daily' ? '%Y-%m-%d' : '%Y-%m';

    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(fe.date, '{$dateFormat}') AS period,
            SUM(CASE WHEN fe.related_type = 1 AND fe.type='debit'   AND fe.user_type='client' THEN fe.amount ELSE 0 END) AS revenue,
            SUM(CASE WHEN fe.related_type = 2 AND fe.type='credit'  AND fe.user_type='vendor' THEN fe.amount ELSE 0 END) AS cogs,
            SUM(CASE WHEN fe.related_type = 5 THEN fe.amount ELSE 0 END) AS discount,
            SUM(CASE WHEN fe.related_type = 1 AND fe.type='debit'   AND fe.user_type='client' THEN fe.amount ELSE 0 END)
            - SUM(CASE WHEN fe.related_type = 2 AND fe.type='credit' AND fe.user_type='vendor' THEN fe.amount ELSE 0 END)
            - SUM(CASE WHEN fe.related_type = 5 THEN fe.amount ELSE 0 END) AS net_profit
        FROM financial_entries fe
        WHERE fe.related_type IN (1,2,5)
          {$whereSql}
        GROUP BY period
        ORDER BY period ASC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true,'period'=>$period,'rows'=>$rows]);
}

/* ============================================================
   DETAIL — row level entries
============================================================ */
function handleDetail(PDO $pdo): void
{
    [$whereSql, $params] = buildProfitFilters();

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(500, max(1, (int)($_GET['per_page'] ?? 50)));
    $offset  = ($page - 1) * $perPage;

    // Count
    $cntStmt = $pdo->prepare("
        SELECT COUNT(*) FROM financial_entries fe
        WHERE fe.related_type IN (1,2,5)
          {$whereSql}
    ");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    $params[':limit']  = $perPage;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare("
        SELECT
            fe.sys_id, fe.date, fe.user_type, fe.user_sys_id, fe.user_name,
            fe.purpose, fe.type, fe.amount, fe.related_type,
            fe.work_sys_id, fe.work_title, fe.task_sys_id, fe.task_title,
            CASE fe.related_type
                WHEN 1 THEN 'Sale'
                WHEN 2 THEN 'Purchase'
                WHEN 5 THEN 'Discount'
            END AS entry_type
        FROM financial_entries fe
        WHERE fe.related_type IN (1,2,5)
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

/* ============================================================
   FILTERS — dropdown data
============================================================ */
function handleFilters(PDO $pdo): void
{
    // Clients
    $clients = $pdo->query("
        SELECT DISTINCT user_sys_id AS id, user_name AS name
        FROM financial_entries
        WHERE user_type = 'client' AND related_type = 1
        ORDER BY user_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Vendors
    $vendors = $pdo->query("
        SELECT DISTINCT user_sys_id AS id, user_name AS name
        FROM financial_entries
        WHERE user_type = 'vendor' AND related_type = 2
        ORDER BY user_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Works
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
        'success' => true,
        'clients' => $clients,
        'vendors' => $vendors,
        'works'   => $works,
        'tasks'   => $tasks,
    ]);
}