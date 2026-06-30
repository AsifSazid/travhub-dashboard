<?php
/**
 * api/reports/receivable/endpoints.php
 *
 * Receivable Report — how much each client still owes.
 * Source table: financial_entries
 *
 * Formula per client:
 *   Receivable = SUM(sale, related_type=1)
 *              - SUM(receive, related_type=3)
 *              - SUM(discount/refund, related_type=5, client side)
 *
 * Actions (?action=...):
 *   list    -> paginated client-wise receivable balances (default)
 *   detail  -> all financial_entries rows for one client (drilldown)
 *   export  -> ALL matching client balances (no pagination)
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../server/db_connection.php'; // expects $pdo

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
        'error'   => 'Server error in receivable report endpoint.',
        'detail'  => $e->getMessage(),
    ]);
}

// ============================================================
// Handlers
// ============================================================

function handleList(PDO $pdo, bool $isExport): void
{
    [$whereSql, $params] = buildFilters();

    // Aggregate per client. Net = sale - receive - discount/refund.
    $sql = "
        SELECT
            fe.user_sys_id,
            fe.user_name,
            SUM(CASE WHEN fe.related_type = 1 THEN fe.amount ELSE 0 END) AS total_sale,
            SUM(CASE WHEN fe.related_type = 3 THEN fe.amount ELSE 0 END) AS total_receive,
            SUM(CASE WHEN fe.related_type = 5 THEN fe.amount ELSE 0 END) AS total_discount,
            (
                SUM(CASE WHEN fe.related_type = 1 THEN fe.amount ELSE 0 END)
                - SUM(CASE WHEN fe.related_type = 3 THEN fe.amount ELSE 0 END)
                - SUM(CASE WHEN fe.related_type = 5 THEN fe.amount ELSE 0 END)
            ) AS receivable_balance,
            MAX(fe.date) AS last_activity_date,
            COUNT(*) AS entry_count
        FROM financial_entries fe
        WHERE fe.user_type = 'client'
          AND fe.related_type IN (1,3,5)
          {$whereSql}
        GROUP BY fe.user_sys_id, fe.user_name
        HAVING receivable_balance != 0
        ORDER BY receivable_balance DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'total_clients'      => count($allRows),
        'total_receivable'   => array_sum(array_column($allRows, 'receivable_balance')),
    ];

    if ($isExport) {
        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'rows'    => $allRows,
        ]);
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
        SELECT id, sys_id, work_sys_id, work_title, task_sys_id, task_title,
               date, purpose, type, amount, ref, related_type
        FROM financial_entries
        WHERE user_type = 'client'
          AND user_sys_id = :user_sys_id
          AND related_type IN (1,3,5)
        ORDER BY date ASC, id ASC
    ");
    $stmt->execute([':user_sys_id' => $clientSysId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'rows' => $rows]);
}

/**
 * Builds WHERE clause (appended, starts with AND) + params from $_GET.
 */
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