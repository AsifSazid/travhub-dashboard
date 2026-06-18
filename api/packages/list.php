<?php
/**
 * api/packages/list.php (Gen-3)
 * GET ?search=&status=active&completion_status=&page=1&limit=15
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

$search     = trim($_GET['search']             ?? '');
$status     = trim($_GET['status']             ?? 'active');
$completion = trim($_GET['completion_status']  ?? '');
$page       = max(1,  (int)($_GET['page']      ?? 1));
$limit      = max(1, min(100, (int)($_GET['limit'] ?? 15)));
$offset     = ($page - 1) * $limit;

try {
    $where  = [];
    $params = [];

    if ($status === 'trash')   $where[] = "status = 'deleted'";
    elseif ($status === 'all') $where[] = "status != 'deleted'";
    else                       $where[] = "status = 'active'";

    if ($completion) { $where[] = "completion_status = :cs"; $params[':cs'] = $completion; }
    if ($search)     { $where[] = "(title LIKE :s OR booking_ref LIKE :s2 OR client_name LIKE :s3)";
                       $params[':s'] = "%{$search}%"; $params[':s2'] = "%{$search}%"; $params[':s3'] = "%{$search}%"; }

    $w   = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM packages {$w}");
    $cnt->execute($params); $total = (int)$cnt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT id, sys_id, uuid, booking_ref, title, package_type, description,
               start_date, end_date, duration, adults, children, infants,
               sell_currency_code, client_sys_id, client_name,
               cover_image, image, progress_step, completion_status,
               active_quote_sys_id, rating, overall_price,
               assigned_to_sys_id, version, status, meta_data
        FROM packages {$w}
        ORDER BY id DESC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success'    => true,
        'data'       => $rows,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => (int)ceil($total / $limit),
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
