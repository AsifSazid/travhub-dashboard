<?php
/**
 * api/masterdata/activities/tag-list.php (Gen-3)
 * GET ?activity_sys_id=THR-26-CNT-01-ACT-01
 * Returns all active tags for an activity.
 *
 * Also supports:
 * GET ?all=1  →  returns all distinct tags in the system (for filter dropdown)
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$activity_sys_id = trim($_GET['activity_sys_id'] ?? '');
$all             = !empty($_GET['all']);

try {
    if ($all) {
        // All distinct active tags across all activities
        $stmt = $pdo->query("
            SELECT tag, COUNT(*) as usage_count
            FROM activity_tags
            WHERE status = 'active'
            GROUP BY tag
            ORDER BY usage_count DESC, tag ASC
        ");
        $tags = $stmt->fetchAll();
        echo json_encode(['success'=>true,'data'=>$tags], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$activity_sys_id) {
        echo json_encode(['success'=>false,'message'=>'activity_sys_id required']); exit;
    }

    $stmt = $pdo->prepare("
        SELECT sys_id, tag
        FROM activity_tags
        WHERE activity_sys_id = ? AND status = 'active'
        ORDER BY tag ASC
    ");
    $stmt->execute([$activity_sys_id]);
    $rows = $stmt->fetchAll();
    $tags = array_column($rows, 'tag');

    echo json_encode([
        'success'          => true,
        'activity_sys_id'  => $activity_sys_id,
        'tags'             => $tags,
        'count'            => count($tags),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}