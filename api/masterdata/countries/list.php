<?php
session_start();
// api/masterdata/countries/list.php
// GET  ?search=&region=&status=active&page=1&limit=20
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');

$search  = trim($_GET['search'] ?? '');
$region  = trim($_GET['region'] ?? '');
$status  = trim($_GET['status'] ?? 'active');
$page    = max(1, (int)($_GET['page']  ?? 1));
$limit   = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset  = ($page - 1) * $limit;

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
        $where[]          = "(name LIKE :search OR code LIKE :search2 OR currency_code LIKE :search3)";
        $params[':search']  = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }

    if ($region !== '') {
        $where[]          = "region = :region";
        $params[':region'] = $region;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM countries $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Fetch page
    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, name, code, currency, currency_code,
               default_rate, region, cities, status, meta_data, created_at, updated_at
        FROM countries
        $whereSQL
        ORDER BY name ASC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
        $cities   = json_decode($row['cities']    ?? '[]', true) ?: [];
        $meta     = json_decode($row['meta_data'] ?? '{}', true) ?: [];
        $data[] = [
            'id'            => (int)$row['id'],
            'uuid'          => $row['uuid'],
            'sys_id'        => $row['sys_id'],
            'name'          => $row['name'],
            'code'          => $row['code'],
            'currency'      => $row['currency'],
            'currency_code' => $row['currency_code'],
            'default_rate'  => (float)$row['default_rate'],
            'region'        => $row['region'],
            'cities'        => $cities,
            'city_count'    => count($cities),
            'status'        => $row['status'],
            'created_by'    => $meta['created_by_date']['user'] ?? 'system',
            'created_at'    => $row['created_at'],
            'updated_at'    => $row['updated_at'],
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