<?php
/**
 * api/reports/asset_v2/endpoints.php
 *
 * Fixed Asset Register. Source: financial_entries, account_head='fixed_asset'.
 * Each row's user_sys_id/user_name is the classification (Fixed
 * Assets-category) ac_banking account (e.g. "Office Laptop", "Furniture").
 * Groups by that account to show total invested value per asset category,
 * plus a full purchase-history drilldown.
 *
 * Note: this reports GROSS purchase value, not depreciated book value --
 * no depreciation schedule exists yet in this system.
 *
 * Actions:
 *   list    -> paginated asset-account-wise totals (default)
 *   detail  -> all fixed_asset entries for one asset account (drilldown)
 *   export  -> ALL matching rows (no pagination)
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
        'error'   => 'Server error in asset_v2 report endpoint.',
        'detail'  => $e->getMessage(),
    ]);
}

function handleList(PDO $pdo, bool $isExport): void
{
    [$whereSql, $params] = buildFilters();

    $sql = "
        SELECT
            fe.user_sys_id AS asset_account_id,
            fe.user_name   AS asset_name,
            SUM(fe.amount) AS total_invested,
            MIN(fe.date)   AS first_purchase_date,
            MAX(fe.date)   AS last_purchase_date,
            COUNT(*)       AS purchase_count
        FROM financial_entries fe
        WHERE fe.account_head = 'fixed_asset'
          {$whereSql}
        GROUP BY fe.user_sys_id, fe.user_name
        ORDER BY total_invested DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'total_asset_accounts' => count($allRows),
        'total_invested_value' => array_sum(array_column($allRows, 'total_invested')),
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
    $assetAccountId = $_GET['asset_account_id'] ?? '';
    if (empty($assetAccountId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'asset_account_id is required']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT id, sys_id, transaction_group_id, date, purpose, amount, ref
        FROM financial_entries
        WHERE account_head = 'fixed_asset'
          AND user_sys_id = :asset_account_id
        ORDER BY date ASC, id ASC
    ");
    $stmt->execute([':asset_account_id' => $assetAccountId]);
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