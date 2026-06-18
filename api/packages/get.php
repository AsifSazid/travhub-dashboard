<?php
/**
 * api/packages/get.php (Gen-3)
 * GET ?sys_id=THR-PK-26-00K001
 * Returns package + all days + all items per day
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

$sys_id = trim($_GET['sys_id'] ?? '');
if (!$sys_id) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }

try {
    $pkg = $pdo->prepare("SELECT * FROM packages WHERE sys_id = ? AND status != 'deleted' LIMIT 1");
    $pkg->execute([$sys_id]);
    $package = $pkg->fetch();
    if (!$package) { echo json_encode(['success'=>false,'message'=>'Package not found']); exit; }

    // decode JSON blob columns
    $jsonCols = ['countries','cities','hotels','pack_itenaries','pack_price',
                 'pack_inclusions','pack_exclusions','pack_components',
                 'pricing_config','highlights','no_of_pax'];
    foreach ($jsonCols as $col) {
        if (!empty($package[$col])) {
            $package[$col] = json_decode($package[$col], true);
        }
    }

    // Load days
    $dStmt = $pdo->prepare("
        SELECT * FROM package_days
        WHERE package_sys_id = ? AND status != 'deleted'
        ORDER BY day_number ASC
    ");
    $dStmt->execute([$sys_id]);
    $days = $dStmt->fetchAll();

    // Load all items for this package (one query, group by day)
    $iStmt = $pdo->prepare("
        SELECT * FROM package_items
        WHERE package_sys_id = ? AND status != 'deleted'
        ORDER BY day_sys_id ASC, sequence ASC
    ");
    $iStmt->execute([$sys_id]);
    $allItems = $iStmt->fetchAll();

    // Group items by day_sys_id
    $itemsByDay = [];
    foreach ($allItems as $item) {
        if (!empty($item['detail'])) {
            $item['detail'] = json_decode($item['detail'], true);
        }
        $itemsByDay[$item['day_sys_id']][] = $item;
    }

    // Attach items to days
    foreach ($days as &$day) {
        $day['items'] = $itemsByDay[$day['sys_id']] ?? [];
    }
    unset($day);

    $package['days'] = $days;

    echo json_encode(['success'=>true,'data'=>$package], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}