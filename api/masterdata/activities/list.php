<?php
session_start();
// api/masterdata/activities/list.php
// GET  ?search=&city_sys_id=&country_sys_id=&type=&status=active&page=1&limit=20
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');

$search         = trim($_GET['search']         ?? '');
$city_sys_id    = trim($_GET['city_sys_id']    ?? '');
$country_sys_id = trim($_GET['country_sys_id'] ?? '');
$type           = trim($_GET['type']           ?? '');
$price_range    = trim($_GET['price_range']    ?? '');
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
        $where[]          = "name LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
 
    if ($city_sys_id !== '') {
        $where[]               = "city_sys_id = :city_sys_id";
        $params[':city_sys_id'] = $city_sys_id;
    }
 
    if ($country_sys_id !== '') {
        $where[]                    = "country_sys_id = :country_sys_id";
        $params[':country_sys_id'] = $country_sys_id;
    }
 
    if ($type !== '') {
        $where[]        = "type = :type";
        $params[':type'] = $type;
    }
 
    if ($price_range !== '') {
        $where[]               = "price_range = :price_range";
        $params[':price_range'] = $price_range;
    }
 
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
 
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM activities $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
 
    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, city_sys_id, country_sys_id,
               name, type, price_range, duration_hours, popularity,
               status, meta_data, created_at, updated_at
        FROM activities
        $whereSQL
        ORDER BY country_sys_id ASC, city_sys_id ASC, name ASC
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
            'id'              => (int)$row['id'],
            'uuid'            => $row['uuid'],
            'sys_id'          => $row['sys_id'],
            'city_sys_id'     => $row['city_sys_id'],
            'country_sys_id'  => $row['country_sys_id'],
            'name'            => $row['name'],
            'type'            => $row['type'],
            'price_range'     => $row['price_range'],
            'duration_hours'  => (float)$row['duration_hours'],
            'popularity'      => (int)$row['popularity'],
            'status'          => $row['status'],
            'created_by'      => $meta['created_by_date']['user'] ?? 'system',
            'created_at'      => $row['created_at'],
            'updated_at'      => $row['updated_at'],
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
 