<?php
/**
 * api/masterdata/countries/toggle-package.php
 * POST { sys_id, for_package: 0|1 }
 * Instantly toggles for_package flag — no full save needed.
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in          = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id      = trim($in['sys_id']      ?? '');
$for_package = isset($in['for_package']) ? (int)(bool)$in['for_package'] : null;

if (!$sys_id || $for_package === null) {
    echo json_encode(['success'=>false,'message'=>'sys_id and for_package required']); exit;
}

try {
    $row = $pdo->prepare("SELECT name, meta_data FROM countries WHERE sys_id = ? AND status != 'deleted' LIMIT 1");
    $row->execute([$sys_id]);
    $existing = $row->fetch();
    if (!$existing) { echo json_encode(['success'=>false,'message'=>'Country not found']); exit; }

    $meta = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("UPDATE countries SET for_package = ?, meta_data = ? WHERE sys_id = ?")
        ->execute([$for_package, $meta, $sys_id]);

    echo json_encode([
        'success'     => true,
        'sys_id'      => $sys_id,
        'name'        => $existing['name'],
        'for_package' => $for_package,
        'message'     => $for_package
            ? "{$existing['name']} enabled for packages."
            : "{$existing['name']} removed from packages.",
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}