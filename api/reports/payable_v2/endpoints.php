<?php
/**
 * api/reports/payable_v2/endpoints.php
 *
 * Payable Report (v2) -- how much you still owe each vendor, computed from
 * the double-entry accounts_payable account_head balance rather than the
 * old related_type guessing.
 *
 * Formula per vendor:
 *   Payable balance = SUM(credit, account_head='accounts_payable')
 *                    - SUM(debit,  account_head='accounts_payable')
 *   (credit increases a liability, debit decreases it -- standard rule)
 *
 * Only rows with account_head IS NOT NULL are included, so pre-migration
 * legacy financial_entries (routed through old_tasks/com_works, never
 * backfilled with a real account_head) are excluded rather than silently
 * mixed in. Use /api/reports/diagnostics/legacy-chain-check.php to see how
 * much legacy data exists separately.
 *
 * Response shape is kept identical to the v1 report (list/detail/export,
 * same field names plus a couple of v2-only additions) so existing frontend
 * pages can switch over with minimal changes.
 *
 * Actions (?action=...):
 *   list    -> paginated vendor-wise payable balances (default)
 *   detail  -> all accounts_payable financial_entries rows for one vendor (drilldown)
 *   export  -> ALL matching vendor balances (no pagination)
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../server/db_connection.php'; // expects $pdo
session_start();
require_once __DIR__ . '/../../../server/permissions.php';
requireFullAccountingAccess($pdo, true);

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'detail':
            handleDetail($pdo);
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
        'error'   => 'Server error in payable_v2 report endpoint.',
        'detail'  => $e->getMessage(),
    ]);
}

function handleList(PDO $pdo, bool $isExport): void
{
    [$whereSql, $params] = buildFilters();

    $sql = "
        SELECT
            fe.user_sys_id,
            fe.user_name,
            SUM(CASE WHEN fe.type = 'credit' THEN fe.amount ELSE 0 END) AS total_purchase_credits,
            SUM(CASE WHEN fe.type = 'debit'  THEN fe.amount ELSE 0 END) AS total_payments_and_reversals,
            (
                SUM(CASE WHEN fe.type = 'credit' THEN fe.amount ELSE 0 END)
                - SUM(CASE WHEN fe.type = 'debit' THEN fe.amount ELSE 0 END)
            ) AS payable_balance,
            MAX(fe.date) AS last_activity_date,
            COUNT(*) AS entry_count,
            COUNT(DISTINCT fe.transaction_group_id) AS group_count
        FROM financial_entries fe
        WHERE fe.user_type = 'vendor'
          AND fe.account_head = 'accounts_payable'
          {$whereSql}
        GROUP BY fe.user_sys_id, fe.user_name
        HAVING payable_balance > 0.01
        ORDER BY payable_balance DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'total_vendors' => count($allRows),
        'total_payable' => array_sum(array_column($allRows, 'payable_balance')),
    ];

    if ($isExport) {
        echo json_encode(['success' => true, 'summary' => $summary, 'rows' => $allRows]);
        return;
    }

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(500, max(1, (int)($_GET['per_page'] ?? 25)));
    $offset  = ($page - 1) * $perPage;
    $rows    = array_slice($allRows, $offset, $perPage);

    echo json_encode([
        'success'  => true,
        'summary'  => $summary,
        'rows'     => $rows,
        'page'     => $page,
        'per_page' => $perPage,
        'total'    => count($allRows),
        'pages'    => (int)ceil(count($allRows) / $perPage),
    ]);
}

function handleDetail(PDO $pdo): void
{
    $vendorSysId = $_GET['user_sys_id'] ?? '';
    if (empty($vendorSysId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'user_sys_id is required']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT id, sys_id, transaction_group_id, work_sys_id, work_title, task_sys_id, task_title,
               date, purpose, type, amount, ref, related_type
        FROM financial_entries
        WHERE user_type = 'vendor'
          AND user_sys_id = :user_sys_id
          AND account_head = 'accounts_payable'
        ORDER BY date ASC, id ASC
    ");
    $stmt->execute([':user_sys_id' => $vendorSysId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'rows' => $rows]);
}

function buildFilters(): array
{
    $where  = [];
    $params = [];

    if (!empty($_GET['date_from'])) {
        $where[] = "fe.date >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = "fe.date <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }
    if (!empty($_GET['search'])) {
        $where[] = "(fe.user_name LIKE :search OR fe.user_sys_id LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sql = '';
    foreach ($where as $cond) {
        $sql .= " AND {$cond}";
    }

    return [$sql, $params];
}