<?php
/**
 * api/masterdata/countries/list.php  (Gen-3 + for_package)
 * GET ?search=&region=&status=active&for_package=&page=1&limit=15
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$search      = trim($_GET['search']      ?? '');
$region      = trim($_GET['region']      ?? '');
$status      = trim($_GET['status']      ?? 'active');
$for_package = trim($_GET['for_package'])      ?? '';   // '' | '0' | '1'
$page        = max(1, (int)($_GET['page']  ?? 1));
$limit       = max(1, min(100, (int)($_GET['limit'] ?? 15)));
$offset      = ($page - 1) * $limit;

try {
    $where = []; $params = [];

    if ($status === 'trash')     $where[] = "status = 'deleted'";
    elseif ($status === 'all')   $where[] = "status != 'deleted'";
    else                         $where[] = "status = 'active'";

    if ($search !== '') {
        $where[] = "(name LIKE :s OR code LIKE :s2 OR currency LIKE :s3 OR currency_code LIKE :s4)";
        $params[':s'] = $params[':s2'] = $params[':s3'] = $params[':s4'] = '%'.$search.'%';
    }
    if ($region !== '') {
        $where[] = "region = :region"; $params[':region'] = $region;
    }
    if ($for_package !== '') {
        $where[] = "for_package = :fp"; $params[':fp'] = (int)$for_package;
    }

    $w = $where ? 'WHERE '.implode(' AND ', $where) : '';

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM countries $w");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, name, code, currency, currency_code,
               default_rate, region, cities, for_package, status
        FROM countries $w
        ORDER BY name ASC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $data = [];
    foreach ($rows as $r) {
        $cities = json_decode($r['cities'] ?? '[]', true) ?: [];
        $data[] = [
            'id'            => (int)$r['id'],
            'uuid'          => $r['uuid'],
            'sys_id'        => $r['sys_id'],
            'name'          => $r['name'],
            'code'          => $r['code'],
            'currency'      => $r['currency'],
            'currency_code' => $r['currency_code'],
            'default_rate'  => (float)$r['default_rate'],
            'region'        => $r['region'],
            'cities'        => $cities,
            'city_count'    => count($cities),
            'for_package'   => (int)$r['for_package'],
            'status'        => $r['status'],
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
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}