<?php
session_start();
// api/masterdata/cars/list.php
// GET  ?country_sys_id=&type=&status=active&search=&page=1&limit=20
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');

$country_sys_id = trim($_GET['country_sys_id'] ?? '');
$type           = trim($_GET['type']           ?? '');
$status         = trim($_GET['status']         ?? 'active');
$search         = trim($_GET['search']         ?? '');
$page           = max(1, (int)($_GET['page']   ?? 1));
$limit          = max(1, min(100, (int)($_GET['limit'] ?? 50)));
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

    if ($country_sys_id !== '') {
        $where[]                    = "country_sys_id = :country_sys_id";
        $params[':country_sys_id'] = $country_sys_id;
    }

    if ($type !== '') {
        $where[]        = "type = :type";
        $params[':type'] = $type;
    }

    if ($search !== '') {
        $where[]          = "name LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM cars $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, country_sys_id, name, type,
               seats, has_luggage, max_luggage, status, meta_data, created_at, updated_at
        FROM cars
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
        $meta   = json_decode($row['meta_data'] ?? '{}', true) ?: [];
        $data[] = [
            'id'             => (int)$row['id'],
            'uuid'           => $row['uuid'],
            'sys_id'         => $row['sys_id'],
            'country_sys_id' => $row['country_sys_id'],
            'name'           => $row['name'],
            'type'           => $row['type'],
            'seats'          => (int)$row['seats'],
            'has_luggage'    => (bool)$row['has_luggage'],
            'max_luggage'    => $row['max_luggage'],
            'status'         => $row['status'],
            'created_by'     => $meta['created_by_date']['user'] ?? 'system',
            'created_at'     => $row['created_at'],
            'updated_at'     => $row['updated_at'],
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