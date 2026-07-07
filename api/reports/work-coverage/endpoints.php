<?php
/**
 * api/reports/work-coverage/endpoints.php
 * Work → Task Coverage Report
 *
 * Sources:
 *   com_works  — all works
 *   old_tasks  — tasks linked via work_sys_id
 *
 * Actions:
 *   list    → paginated works with task counts
 *   filters → client/status/employee dropdowns
 *   export  → all data
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../server/db_connection.php';

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'filters': handleFilters($pdo); break;
        case 'export':  handleList($pdo, true); break;
        default:        handleList($pdo, false); break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

/* ============================================================
   BUILD FILTERS
============================================================ */
function buildFilters(): array {
    $where  = [];
    $params = [];

    if (!empty($_GET['date_from'])) {
        $where[]              = "JSON_UNQUOTE(JSON_EXTRACT(w.meta_data,'$.created_by_date.date')) >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[]            = "JSON_UNQUOTE(JSON_EXTRACT(w.meta_data,'$.created_by_date.date')) <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }
    if (!empty($_GET['client_id'])) {
        $where[]               = 'w.client_sys_id = :client_id';
        $params[':client_id']  = $_GET['client_id'];
    }
    if (!empty($_GET['work_status'])) {
        $where[]               = 'w.status = :wstatus';
        $params[':wstatus']    = $_GET['work_status'];
    }
    if (!empty($_GET['owned_by'])) {
        $where[]               = 'w.owned_by LIKE :owned_by';
        $params[':owned_by']   = $_GET['owned_by'] . '%';
    }
    if (!empty($_GET['coverage'])) {
        // 'covered' | 'not_covered'
        // HAVING এ handle করবো
    }
    if (!empty($_GET['search'])) {
        $where[]           = '(w.title LIKE :search OR w.client_name LIKE :search OR w.sys_id LIKE :search)';
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sql = empty($where) ? '' : 'AND ' . implode(' AND ', $where);
    return [$sql, $params];
}

/* ============================================================
   LIST — works with task counts
============================================================ */
function handleList(PDO $pdo, bool $isExport): void {
    [$whereSql, $params] = buildFilters();

    $coverage = $_GET['coverage'] ?? ''; // 'covered' | 'not_covered' | ''
    $havingSql = '';
    if ($coverage === 'covered')     $havingSql = 'HAVING task_count > 0';
    if ($coverage === 'not_covered') $havingSql = 'HAVING task_count = 0';

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $isExport ? 99999 : min(100, max(1, (int)($_GET['per_page'] ?? 25)));
    $offset  = ($page - 1) * $perPage;

    // Count query
    $cntSql = "
        SELECT COUNT(*) FROM (
            SELECT w.sys_id
            FROM com_works w
            LEFT JOIN old_tasks t ON t.work_sys_id = w.sys_id
            WHERE 1=1 {$whereSql}
            GROUP BY w.sys_id
            {$havingSql}
        ) sub
    ";
    $cntStmt = $pdo->prepare($cntSql);
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    // Main query
    $params[':limit']  = $perPage;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare("
        SELECT
            w.sys_id          AS work_sys_id,
            w.title           AS work_title,
            w.client_sys_id,
            w.client_name,
            w.status          AS work_status,
            w.owned_by,
            JSON_UNQUOTE(JSON_EXTRACT(w.meta_data,'$.created_by_date.date')) AS created_date,
            COUNT(t.id)       AS task_count,
            SUM(CASE WHEN t.status = 'completed'               THEN 1 ELSE 0 END) AS tasks_done,
            SUM(CASE WHEN t.status IN ('in_progress','inprogress') THEN 1 ELSE 0 END) AS tasks_inprogress,
            SUM(CASE WHEN t.status = 'pending'                 THEN 1 ELSE 0 END) AS tasks_pending,
            GROUP_CONCAT(
                t.sys_id, '||', COALESCE(t.title,''), '||', COALESCE(t.status,''), '||', COALESCE(t.performed_by,'')
                ORDER BY t.id ASC SEPARATOR ';;'
            ) AS tasks_raw
        FROM com_works w
        LEFT JOIN old_tasks t ON t.work_sys_id = w.sys_id
        WHERE 1=1 {$whereSql}
        GROUP BY w.sys_id, w.title, w.client_sys_id, w.client_name,
                 w.status, w.owned_by, created_date
        {$havingSql}
        ORDER BY task_count ASC, w.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse tasks_raw into array
    foreach ($rows as &$row) {
        $row['task_count']      = (int)$row['task_count'];
        $row['tasks_done']      = (int)$row['tasks_done'];
        $row['tasks_inprogress']= (int)$row['tasks_inprogress'];
        $row['tasks_pending']   = (int)$row['tasks_pending'];
        $row['is_covered']      = $row['task_count'] > 0;
        $tasks = [];
        if (!empty($row['tasks_raw'])) {
            foreach (explode(';;', $row['tasks_raw']) as $tr) {
                $parts = explode('||', $tr, 4);
                if (count($parts) >= 3) {
                    $tasks[] = [
                        'sys_id'       => $parts[0],
                        'title'        => $parts[1],
                        'status'       => $parts[2],
                        'performed_by' => $parts[3] ?? '',
                    ];
                }
            }
        }
        $row['tasks'] = $tasks;
        unset($row['tasks_raw']);
    }
    unset($row);

    // Summary counts
    $summaryStmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT w.sys_id) AS total_works,
            SUM(CASE WHEN t.id IS NOT NULL THEN 0 ELSE 1 END) AS not_covered,
            COUNT(DISTINCT CASE WHEN t.id IS NOT NULL THEN w.sys_id END) AS covered,
            COUNT(t.id) AS total_tasks
        FROM com_works w
        LEFT JOIN old_tasks t ON t.work_sys_id = w.sys_id
        WHERE 1=1 {$whereSql}
    ");
    $cParams = $params;
    unset($cParams[':limit'], $cParams[':offset']);
    $summaryStmt->execute($cParams);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'rows'     => $rows,
        'summary'  => $summary,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int)ceil($total / $perPage),
    ]);
}

/* ============================================================
   FILTERS
============================================================ */
function handleFilters(PDO $pdo): void {
    $clients = $pdo->query("
        SELECT DISTINCT client_sys_id AS id, client_name AS name
        FROM com_works WHERE client_sys_id IS NOT NULL AND client_sys_id != ''
        ORDER BY client_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $statuses = $pdo->query("
        SELECT DISTINCT status FROM com_works
        WHERE status IS NOT NULL AND status != ''
        ORDER BY status ASC
    ")->fetchAll(PDO::FETCH_COLUMN);

    $owners = $pdo->query("
        SELECT DISTINCT owned_by FROM com_works
        WHERE owned_by IS NOT NULL AND owned_by != ''
        ORDER BY owned_by ASC
    ")->fetchAll(PDO::FETCH_COLUMN);

    $employees = array_map(function($raw) {
        $parts = explode('|', $raw, 2);
        return ['id' => trim($parts[0]), 'name' => trim($parts[1] ?? $raw), 'raw' => $raw];
    }, $owners);

    echo json_encode([
        'success'   => true,
        'clients'   => $clients,
        'statuses'  => $statuses,
        'employees' => $employees,
    ]);
}