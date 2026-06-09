<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$sys_id = trim($_GET['sys_id'] ?? '');
if (!$sys_id) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }
try {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sys_id]); $row = $stmt->fetch();
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    $row['id'] = (int)$row['id'];
    foreach (['meta_data','amenities','images','extra_charges','attributes','inclusions','exclusions','itineraries','pickup_from_city','dropoff_city'] as $j)
        if (isset($row[$j])) $row[$j] = json_decode($row[$j] ?? '[]', true) ?: [];
    echo json_encode(['success'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
