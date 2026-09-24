<?php
/**
 * api/reports/receivable_v2/endpoints.php
 *
 * Receivable Report (v2) -- how much each client still owes you, computed
 * from the double-entry accounts_receivable account_head balance.
 *
 * Formula per client:
 *   Receivable balance = SUM(debit,  account_head='accounts_receivable')
 *                       - SUM(credit, account_head='accounts_receivable')
 *   (debit increases an asset, credit decreases it)
 *
 * Only rows with account_head IS NOT NULL are included (see payable_v2 for
 * the legacy-data rationale).
 *
 * Actions (?action=...):
 *   list    -> paginated client-wise receivable balances (default)
 *   detail  -> all accounts_receivable financial_entries rows for one client
 *   export  -> ALL matching client balances (no pagination)
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
        'error'   => 'Server error in receivable_v2 report endpoint.',
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
            SUM(CASE WHEN fe.type = 'debit'  THEN fe.amount ELSE 0 END) AS total_sales_debits,
            SUM(CASE WHEN fe.type = 'credit' THEN fe.amount ELSE 0 END) AS total_receives_and_reversals,
            (
                SUM(CASE WHEN fe.type = 'debit'  THEN fe.amount ELSE 0 END)
                - SUM(CASE WHEN fe.type = 'credit' THEN fe.amount ELSE 0 END)
            ) AS receivable_balance,
            MAX(fe.date) AS last_activity_date,
            COUNT(*) AS entry_count,
            COUNT(DISTINCT fe.transaction_group_id) AS group_count
        FROM financial_entries fe
        WHERE fe.user_type = 'client'
          AND fe.account_head = 'accounts_receivable'
          {$whereSql}
        GROUP BY fe.user_sys_id, fe.user_name
        HAVING receivable_balance > 0.01
        ORDER BY receivable_balance DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'total_clients'    => count($allRows),
        'total_receivable' => array_sum(array_column($allRows, 'receivable_balance')),
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
    $clientSysId = $_GET['user_sys_id'] ?? '';
    if (empty($clientSysId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'user_sys_id is required']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT id, sys_id, transaction_group_id, work_sys_id, work_title, task_sys_id, task_title,
               date, purpose, type, amount, ref, related_type
        FROM financial_entries
        WHERE user_type = 'client'
          AND user_sys_id = :user_sys_id
          AND account_head = 'accounts_receivable'
        ORDER BY date ASC, id ASC
    ");
    $stmt->execute([':user_sys_id' => $clientSysId]);
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