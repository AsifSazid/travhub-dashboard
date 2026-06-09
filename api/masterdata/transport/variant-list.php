<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'active');
$page   = max(1, (int)($_GET['page']  ?? 1));
$limit  = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

try {
    $where = []; $params = [];
    if ($status === 'trash')     $where[] = "status = 'deleted'";
    elseif ($status === 'all')   $where[] = "status != 'deleted'";
    else                         $where[] = "status = 'active'";
$parent = trim($_GET['service_sys_id'] ?? '');
if ($parent) { $where[] = "service_sys_id = :pf"; $params[':pf'] = $parent; }
    $w = $where ? 'WHERE '.implode(' AND ', $where) : '';
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM transport_variants $w");
    $cnt->execute($params); $total = (int)$cnt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM transport_variants $w ORDER BY variant_name ASC LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute(); $rows = $stmt->fetchAll();
    foreach ($rows as &$r) { $r['id'] = (int)$r['id']; }
    echo json_encode(['success'=>true,'data'=>$rows,'pagination'=>['total'=>$total,'page'=>$page,'limit'=>$limit,'total_pages'=>(int)ceil($total/$limit)]], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
