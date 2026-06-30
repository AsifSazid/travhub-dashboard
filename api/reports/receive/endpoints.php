<?php
/**
 * api/reports/receive/endpoints.php
 *
 * Action-routed API for the Receive Report.
 * Source table: ac_banking_stmts (related_type = 1 => receive)
 *
 * Actions (?action=...):
 *   list     -> paginated rows + summary (default)
 *   filters  -> bank list + method list for dropdowns
 *   export   -> ALL matching rows (no pagination), used by CSV/Excel/PDF export
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../server/db_connection.php'; // expects $pdo (PDO instance) - adjust to your actual bootstrap

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
        'error'   => 'Server error in receive report endpoint.',
        'detail'  => $e->getMessage(), // remove/hide in production
    ]);
}

// ============================================================
// Handlers
// ============================================================

function handleFilters(PDO $pdo): void
{
    $stmt = $pdo->query("
        SELECT sys_id, acc_name
        FROM ac_banking
        WHERE is_transactionable = 'yes'
        ORDER BY acc_name ASC
    ");
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'banks'   => $banks,
        'methods' => [
            ['value' => 'cash',      'label' => 'Cash'],
            ['value' => 'cheque',    'label' => 'Cheque'],
            ['value' => 'npsb-rtgs', 'label' => 'NPSB / RTGS'],
            ['value' => 'bftn-eft',  'label' => 'BFTN / EFT'],
        ],
    ]);
}

function handleList(PDO $pdo, bool $isExport): void
{
    [$whereSql, $params] = buildFilters();

    $baseSql = "FROM ac_banking_stmts bs WHERE {$whereSql}";

    // Summary always computed on the full filtered set
    $sumStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total_count,
            COALESCE(SUM(CAST(bs.deposit AS DECIMAL(15,2))), 0) AS total_amount
        {$baseSql}
    ");
    $sumStmt->execute($params);
    $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

    $orderSql = " ORDER BY bs.date DESC, bs.id DESC ";
    $selectCols = "
        bs.id, bs.uuid, bs.sys_id, bs.ledger_db_id, bs.name AS bank_name,
        bs.transfer_type, bs.transfer_method, bs.date, bs.particular,
        bs.deposit AS amount, bs.balance, bs.reconsilation, bs.ref
    ";

    if ($isExport) {
        $stmt = $pdo->prepare("SELECT {$selectCols} {$baseSql} {$orderSql}");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'rows'    => $rows,
        ]);
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

/**
 * Builds WHERE clause + bound params from $_GET filters.
 * Shared by list and export so both honor identical filter logic.
 */
function buildFilters(): array
{
    $where  = ["bs.related_type = 1"]; // receive only
    $params = [];

    if (!empty($_GET['date_from'])) {
        $where[] = "DATE(bs.date) >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = "DATE(bs.date) <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }

    if (!empty($_GET['bank']) && is_array($_GET['bank'])) {
        $keys = [];
        foreach ($_GET['bank'] as $i => $val) {
            $k = ":bank{$i}";
            $keys[] = $k;
            $params[$k] = $val;
        }
        $where[] = "bs.ledger_db_id IN (" . implode(',', $keys) . ")";
    }

    if (!empty($_GET['method']) && is_array($_GET['method'])) {
        $keys = [];
        foreach ($_GET['method'] as $i => $val) {
            $k = ":method{$i}";
            $keys[] = $k;
            $params[$k] = $val;
        }
        $where[] = "bs.transfer_method IN (" . implode(',', $keys) . ")";
    }

    if (!empty($_GET['amount_min'])) {
        $where[] = "CAST(bs.deposit AS DECIMAL(15,2)) >= :amount_min";
        $params[':amount_min'] = $_GET['amount_min'];
    }
    if (!empty($_GET['amount_max'])) {
        $where[] = "CAST(bs.deposit AS DECIMAL(15,2)) <= :amount_max";
        $params[':amount_max'] = $_GET['amount_max'];
    }

    if (isset($_GET['reconsilation']) && $_GET['reconsilation'] !== '') {
        $where[] = $_GET['reconsilation'] === '1'
            ? "(bs.reconsilation IS NOT NULL AND bs.reconsilation != '')"
            : "(bs.reconsilation IS NULL OR bs.reconsilation = '')";
    }

    if (!empty($_GET['search'])) {
        $where[] = "(bs.particular LIKE :search OR bs.ref LIKE :search OR bs.name LIKE :search OR bs.sys_id LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    return [implode(' AND ', $where), $params];
}