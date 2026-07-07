<?php
/**
 * api/reports/kpi/endpoints.php
 * Employee KPI Report
 *
 * Three separate KPI views:
 *   1. Created By — meta_data → created_by_date.user
 *   2. Owned By   — com_works.owned_by
 *   3. Performed By — old_tasks.performed_by
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../server/db_connection.php';

$action = $_GET['action'] ?? 'summary';

try {
    switch ($action) {
        case 'detail':  handleDetail($pdo);  break;
        case 'filters': handleFilters($pdo); break;
        case 'export':  handleSummary($pdo, true); break;
        default:        handleSummary($pdo, false); break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

/* ============================================================
   PARSE "EMP-ID | Name" format
   Returns: ['id'=>'EMP-1062202','name'=>'Tarekul Islam']
============================================================ */
function parseEmployee(string $raw): array {
    $parts = explode('|', $raw, 2);
    return [
        'id'   => trim($parts[0] ?? ''),
        'name' => trim($parts[1] ?? $raw),
    ];
}

/* ============================================================
   BUILD FILTERS
============================================================ */
function buildFilters(): array {
    $wWhere = []; $tWhere = []; $params = [];

    if (!empty($_GET['date_from'])) {
        $wWhere[] = "JSON_UNQUOTE(JSON_EXTRACT(w.meta_data, '$.created_by_date.date')) >= :date_from_w";
        $tWhere[] = "JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.date')) >= :date_from_t";
        $params[':date_from_w'] = $_GET['date_from'];
        $params[':date_from_t'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $wWhere[] = "JSON_UNQUOTE(JSON_EXTRACT(w.meta_data, '$.created_by_date.date')) <= :date_to_w";
        $tWhere[] = "JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.date')) <= :date_to_t";
        $params[':date_to_w'] = $_GET['date_to'];
        $params[':date_to_t'] = $_GET['date_to'];
    }
    if (!empty($_GET['employee_id'])) {
        $wWhere[] = "JSON_UNQUOTE(JSON_EXTRACT(w.meta_data, '$.created_by_date.user')) LIKE :emp_w";
        $tWhere[] = "JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.user')) LIKE :emp_t";
        $params[':emp_w'] = '%' . $_GET['employee_id'] . '%';
        $params[':emp_t'] = '%' . $_GET['employee_id'] . '%';
    }
    if (!empty($_GET['work_status'])) {
        $wWhere[] = "w.status = :wstatus";
        $params[':wstatus'] = $_GET['work_status'];
    }
    if (!empty($_GET['task_status'])) {
        $tWhere[] = "t.status = :tstatus";
        $params[':tstatus'] = $_GET['task_status'];
    }

    $wSql = empty($wWhere) ? '' : 'AND ' . implode(' AND ', $wWhere);
    $tSql = empty($tWhere) ? '' : 'AND ' . implode(' AND ', $tWhere);

    return [$wSql, $tSql, $params];
}

/* ============================================================
   SUMMARY — Three separate KPI views
============================================================ */
function handleSummary(PDO $pdo, bool $isExport): void {
    [$wSql, $tSql, $params] = buildFilters();

    // ============================================================
    // TABLE 1: Created By (from meta_data)
    // ============================================================
    $createdWorkParams = array_filter($params, fn($k) => str_contains($k, '_w'), ARRAY_FILTER_USE_KEY);
    $createdWorkStmt = $pdo->prepare("
        SELECT
            JSON_UNQUOTE(JSON_EXTRACT(w.meta_data, '$.created_by_date.user')) AS created_by,
            COUNT(*) AS total_works,
            SUM(CASE WHEN w.status = 'completed' THEN 1 ELSE 0 END) AS completed_works,
            SUM(CASE WHEN w.status = 'in_progress' OR w.status = 'inprogress' THEN 1 ELSE 0 END) AS inprogress_works,
            SUM(CASE WHEN w.status = 'pending' THEN 1 ELSE 0 END) AS pending_works,
            SUM(CASE WHEN w.status IS NULL OR w.status = '' THEN 1 ELSE 0 END) AS no_status_works
        FROM com_works w
        WHERE JSON_UNQUOTE(JSON_EXTRACT(w.meta_data, '$.created_by_date.user')) IS NOT NULL
          AND JSON_UNQUOTE(JSON_EXTRACT(w.meta_data, '$.created_by_date.user')) != ''
          {$wSql}
        GROUP BY created_by
        ORDER BY total_works DESC
    ");
    $createdWorkStmt->execute($createdWorkParams);
    $createdWorkRows = $createdWorkStmt->fetchAll(PDO::FETCH_ASSOC);

    $createdTaskParams = array_filter($params, fn($k) => str_contains($k, '_t'), ARRAY_FILTER_USE_KEY);
    $createdTaskStmt = $pdo->prepare("
        SELECT
            JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.user')) AS created_by,
            COUNT(*) AS total_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
            SUM(CASE WHEN t.status = 'in_progress' OR t.status = 'inprogress' THEN 1 ELSE 0 END) AS inprogress_tasks,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) AS pending_tasks,
            SUM(CASE WHEN t.status IS NULL OR t.status = '' THEN 1 ELSE 0 END) AS no_status_tasks
        FROM old_tasks t
        WHERE JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.user')) IS NOT NULL
          AND JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.user')) != ''
          {$tSql}
        GROUP BY created_by
        ORDER BY total_tasks DESC
    ");
    $createdTaskStmt->execute($createdTaskParams);
    $createdTaskRows = $createdTaskStmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge Created By data
    $createdByData = mergeEmployeeData($createdWorkRows, $createdTaskRows, 'created_by');

    // ============================================================
    // TABLE 2: Owned By (com_works.owned_by)
    // ============================================================
    $ownedParams = array_filter($params, fn($k) => str_contains($k, '_w'), ARRAY_FILTER_USE_KEY);
    $ownedStmt = $pdo->prepare("
        SELECT
            w.owned_by,
            COUNT(*) AS total_works,
            SUM(CASE WHEN w.status = 'completed' THEN 1 ELSE 0 END) AS completed_works,
            SUM(CASE WHEN w.status = 'in_progress' OR w.status = 'inprogress' THEN 1 ELSE 0 END) AS inprogress_works,
            SUM(CASE WHEN w.status = 'pending' THEN 1 ELSE 0 END) AS pending_works,
            SUM(CASE WHEN w.status IS NULL OR w.status = '' THEN 1 ELSE 0 END) AS no_status_works
        FROM com_works w
        WHERE w.owned_by IS NOT NULL AND w.owned_by != ''
          {$wSql}
        GROUP BY w.owned_by
        ORDER BY total_works DESC
    ");
    $ownedStmt->execute($ownedParams);
    $ownedRows = $ownedStmt->fetchAll(PDO::FETCH_ASSOC);

    // ============================================================
    // TABLE 3: Performed By (old_tasks.performed_by)
    // ============================================================
    $performedParams = array_filter($params, fn($k) => str_contains($k, '_t'), ARRAY_FILTER_USE_KEY);
    $performedStmt = $pdo->prepare("
        SELECT
            t.performed_by,
            COUNT(*) AS total_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
            SUM(CASE WHEN t.status = 'in_progress' OR t.status = 'inprogress' THEN 1 ELSE 0 END) AS inprogress_tasks,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) AS pending_tasks,
            SUM(CASE WHEN t.status IS NULL OR t.status = '' THEN 1 ELSE 0 END) AS no_status_tasks
        FROM old_tasks t
        WHERE t.performed_by IS NOT NULL AND t.performed_by != ''
          {$tSql}
        GROUP BY t.performed_by
        ORDER BY total_tasks DESC
    ");
    $performedStmt->execute($performedParams);
    $performedRows = $performedStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format Owned By data
    $ownedByData = [];
    foreach ($ownedRows as $w) {
        $emp = parseEmployee($w['owned_by']);
        $key = $emp['id'] ?: $emp['name'];
        $ownedByData[$key] = [
            'emp_id' => $emp['id'],
            'emp_name' => $emp['name'],
            'total_works' => (int)$w['total_works'],
            'completed_works' => (int)$w['completed_works'],
            'inprogress_works' => (int)$w['inprogress_works'],
            'pending_works' => (int)$w['pending_works'],
            'no_status_works' => (int)$w['no_status_works'],
        ];
    }

    // Format Performed By data
    $performedByData = [];
    foreach ($performedRows as $t) {
        $emp = parseEmployee($t['performed_by']);
        $key = $emp['id'] ?: $emp['name'];
        $performedByData[$key] = [
            'emp_id' => $emp['id'],
            'emp_name' => $emp['name'],
            'total_tasks' => (int)$t['total_tasks'],
            'completed_tasks' => (int)$t['completed_tasks'],
            'inprogress_tasks' => (int)$t['inprogress_tasks'],
            'pending_tasks' => (int)$t['pending_tasks'],
            'no_status_tasks' => (int)$t['no_status_tasks'],
        ];
    }

    // Calculate totals for each view
    $createdTotals = calculateTotals($createdByData, 'created');
    $ownedTotals = calculateTotals($ownedByData, 'owned');
    $performedTotals = calculateTotals($performedByData, 'performed');

    echo json_encode([
        'success' => true,
        'created_by' => [
            'employees' => array_values($createdByData),
            'totals' => $createdTotals
        ],
        'owned_by' => [
            'employees' => array_values($ownedByData),
            'totals' => $ownedTotals
        ],
        'performed_by' => [
            'employees' => array_values($performedByData),
            'totals' => $performedTotals
        ]
    ]);
}

/* ============================================================
   MERGE EMPLOYEE DATA (for Created By)
============================================================ */
function mergeEmployeeData(array $workRows, array $taskRows, string $field): array {
    $employees = [];

    // Add works
    foreach ($workRows as $w) {
        $emp = parseEmployee($w[$field]);
        $key = $emp['id'] ?: $emp['name'];
        if (!isset($employees[$key])) {
            $employees[$key] = [
                'emp_id' => $emp['id'],
                'emp_name' => $emp['name'],
                'total_works' => 0,
                'completed_works' => 0,
                'inprogress_works' => 0,
                'pending_works' => 0,
                'no_status_works' => 0,
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'inprogress_tasks' => 0,
                'pending_tasks' => 0,
                'no_status_tasks' => 0,
            ];
        }
        $employees[$key]['total_works'] += (int)$w['total_works'];
        $employees[$key]['completed_works'] += (int)$w['completed_works'];
        $employees[$key]['inprogress_works'] += (int)$w['inprogress_works'];
        $employees[$key]['pending_works'] += (int)$w['pending_works'];
        $employees[$key]['no_status_works'] += (int)$w['no_status_works'];
    }

    // Add tasks
    foreach ($taskRows as $t) {
        $emp = parseEmployee($t[$field]);
        $key = $emp['id'] ?: $emp['name'];
        if (!isset($employees[$key])) {
            $employees[$key] = [
                'emp_id' => $emp['id'],
                'emp_name' => $emp['name'],
                'total_works' => 0,
                'completed_works' => 0,
                'inprogress_works' => 0,
                'pending_works' => 0,
                'no_status_works' => 0,
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'inprogress_tasks' => 0,
                'pending_tasks' => 0,
                'no_status_tasks' => 0,
            ];
        }
        $employees[$key]['total_tasks'] += (int)$t['total_tasks'];
        $employees[$key]['completed_tasks'] += (int)$t['completed_tasks'];
        $employees[$key]['inprogress_tasks'] += (int)$t['inprogress_tasks'];
        $employees[$key]['pending_tasks'] += (int)$t['pending_tasks'];
        $employees[$key]['no_status_tasks'] += (int)$t['no_status_tasks'];
    }

    // Calculate completion rate
    foreach ($employees as &$emp) {
        $total = $emp['total_works'] + $emp['total_tasks'];
        $done = $emp['completed_works'] + $emp['completed_tasks'];
        $emp['total_items'] = $total;
        $emp['completed_items'] = $done;
        $emp['completion_rate'] = $total > 0 ? round(($done / $total) * 100, 1) : 0;
    }
    unset($emp);

    // Sort by total_items desc
    usort($employees, fn($a, $b) => $b['total_items'] - $a['total_items']);
    
    return $employees;
}

/* ============================================================
   CALCULATE TOTALS
============================================================ */
function calculateTotals(array $data, string $type): array {
    $totals = [
        'total_employees' => count($data),
        'total_works' => 0,
        'total_tasks' => 0,
        'completed_works' => 0,
        'completed_tasks' => 0,
    ];

    foreach ($data as $emp) {
        $totals['total_works'] += $emp['total_works'] ?? 0;
        $totals['total_tasks'] += $emp['total_tasks'] ?? 0;
        $totals['completed_works'] += $emp['completed_works'] ?? 0;
        $totals['completed_tasks'] += $emp['completed_tasks'] ?? 0;
    }

    return $totals;
}

/* ============================================================
   DETAIL — work+task list per employee (by created_by)
============================================================ */
function handleDetail(PDO $pdo): void {
    $empId = $_GET['employee_id'] ?? '';
    if (!$empId) { 
        echo json_encode(['success'=>false,'error'=>'employee_id required']); 
        return; 
    }

    // Works created by this employee
    $wStmt = $pdo->prepare("
        SELECT
            w.sys_id, w.title, w.status, w.client_name,
            w.owned_by,
            JSON_UNQUOTE(JSON_EXTRACT(w.meta_data, '$.created_by_date.date')) AS created_date,
            JSON_UNQUOTE(JSON_EXTRACT(w.meta_data, '$.created_by_date.user')) AS created_by,
            'work' AS item_type
        FROM com_works w
        WHERE JSON_UNQUOTE(JSON_EXTRACT(w.meta_data, '$.created_by_date.user')) LIKE :emp
        ORDER BY w.id DESC
    ");
    $wStmt->execute([':emp' => '%' . $empId . '%']);
    $works = $wStmt->fetchAll(PDO::FETCH_ASSOC);

    // Tasks created by this employee
    $tStmt = $pdo->prepare("
        SELECT
            t.sys_id, t.title, t.status, t.work_title AS client_name,
            t.performed_by,
            JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.date')) AS created_date,
            JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.user')) AS created_by,
            t.work_sys_id, t.category,
            'task' AS item_type
        FROM old_tasks t
        WHERE JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.user')) LIKE :emp
        ORDER BY t.id DESC
    ");
    $tStmt->execute([':emp' => '%' . $empId . '%']);
    $tasks = $tStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true, 'works'=>$works, 'tasks'=>$tasks]);
}

/* ============================================================
   FILTERS — dropdown data
============================================================ */
function handleFilters(PDO $pdo): void {
    // All employees from created_by (meta_data)
    $employees = [];
    
    $wEmp = $pdo->query("
        SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.user')) AS emp 
        FROM com_works 
        WHERE JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.user')) IS NOT NULL 
          AND JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.user')) != ''
        ORDER BY emp
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    $tEmp = $pdo->query("
        SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.user')) AS emp 
        FROM old_tasks 
        WHERE JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.user')) IS NOT NULL 
          AND JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.user')) != ''
        ORDER BY emp
    ")->fetchAll(PDO::FETCH_COLUMN);

    $allRaw = array_unique(array_merge($wEmp, $tEmp));
    sort($allRaw);
    $employees = array_map(fn($r) => parseEmployee($r), $allRaw);

    // Work statuses
    $wStatus = $pdo->query("SELECT DISTINCT status FROM com_works WHERE status IS NOT NULL AND status != '' ORDER BY status")->fetchAll(PDO::FETCH_COLUMN);

    // Task statuses
    $tStatus = $pdo->query("SELECT DISTINCT status FROM old_tasks WHERE status IS NOT NULL AND status != '' ORDER BY status")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'work_statuses' => $wStatus,
        'task_statuses' => $tStatus,
    ]);
}
?>