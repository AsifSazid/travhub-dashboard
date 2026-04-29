<?php
session_start();
// api/masterdata/activities/list.php
// GET  ?search=&country_sys_id=&type=&status=active&page=1&limit=20
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');

$search         = trim($_GET['search']         ?? '');
$country_sys_id = trim($_GET['country_sys_id'] ?? '');
$type           = trim($_GET['type']           ?? '');
$status         = trim($_GET['status']         ?? 'active');
$page           = max(1, (int)($_GET['page']   ?? 1));
$limit          = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset         = ($page - 1) * $limit;

try {
    $where  = [];
    $params = [];

    if ($status === 'trash') {
        $where[] = "status = 'deleted'";
    } elseif ($status === 'all') {
        $where[] = "status != 'deleted'";
    } else {
        $where[] = "status = 'active'";
    }

    if ($search !== '') {
        $where[]           = "(name LIKE :search OR location LIKE :search2)";
        $params[':search']  = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
    }

    if ($country_sys_id !== '') {
        $where[]                   = "country_sys_id = :country_sys_id";
        $params[':country_sys_id'] = $country_sys_id;
    }

    if ($type !== '') {
        $where[]        = "type = :type";
        $params[':type'] = $type;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM activities $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, country_sys_id, name, type, location,
               start_time, end_time, duration_hours, popularity,
               pickup_from_city, dropoff_city, transfers,
               status, meta_data, created_at, updated_at
        FROM activities
        $whereSQL
        ORDER BY country_sys_id ASC, name ASC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
        $meta      = json_decode($row['meta_data'] ?? '{}', true) ?: [];
        $transfers = json_decode($row['transfers'] ?? '[]', true) ?: [];
        $data[] = [
            'id'               => (int)$row['id'],
            'uuid'             => $row['uuid'],
            'sys_id'           => $row['sys_id'],
            'country_sys_id'   => $row['country_sys_id'],
            'name'             => $row['name'],
            'type'             => $row['type'],
            'location'         => $row['location'],
            'start_time'       => $row['start_time'],
            'end_time'         => $row['end_time'],
            'duration_hours'   => (float)($row['duration_hours'] ?? 0),
            'popularity'       => (int)($row['popularity'] ?? 3),
            'pickup_from_city' => json_decode($row['pickup_from_city'] ?? '[]', true) ?: [],
            'dropoff_city'     => json_decode($row['dropoff_city']     ?? '[]', true) ?: [],
            'transfer_count'   => count($transfers),
            'status'           => $row['status'],
            'created_by'       => $meta['created_by_date']['user'] ?? 'system',
            'created_at'       => $row['created_at'],
            'updated_at'       => $row['updated_at'],
        ];
    }

    echo json_encode([
        'success'    => true,
        'data'       => $data,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => (int)ceil($total / $limit),
        ],
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}