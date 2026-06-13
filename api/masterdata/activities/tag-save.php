<?php
/**
 * api/masterdata/activities/tag-save.php (Gen-3)
 * POST { activity_sys_id, tags: ["beach","family","custom_tag"] }
 * Full replace — deletes existing tags for this activity, inserts new set.
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in             = json_decode(file_get_contents('php://input'), true) ?: [];
$activity_sys_id = trim($in['activity_sys_id'] ?? '');
$tags            = is_array($in['tags'] ?? null) ? $in['tags'] : [];

if (!$activity_sys_id) {
    echo json_encode(['success'=>false,'message'=>'activity_sys_id required']); exit;
}

// Sanitize tags — lowercase, alphanumeric + underscore only, max 50 chars
$tags = array_values(array_unique(array_filter(array_map(function($t) {
    $t = strtolower(trim($t));
    $t = preg_replace('/[^a-z0-9_]/', '_', $t);
    $t = preg_replace('/_+/', '_', $t);
    $t = trim($t, '_');
    return strlen($t) > 0 && strlen($t) <= 50 ? $t : null;
}, $tags))));

try {
    $pdo->beginTransaction();

    // Soft-delete all existing tags for this activity
    $pdo->prepare("UPDATE activity_tags SET status='deleted' WHERE activity_sys_id=?")
        ->execute([$activity_sys_id]);

    // Insert new tags
    foreach ($tags as $tag) {
        // Check if tag existed before (reactivate)
        $existing = $pdo->prepare("SELECT sys_id, meta_data FROM activity_tags WHERE activity_sys_id=? AND tag=? LIMIT 1");
        $existing->execute([$activity_sys_id, $tag]);
        $row = $existing->fetch();

        if ($row) {
            $meta = buildMetaData($row['meta_data'], $_SESSION['user_name'] ?? 'system');
            $pdo->prepare("UPDATE activity_tags SET status='active', meta_data=? WHERE sys_id=?")
                ->execute([$meta, $row['sys_id']]);
        } else {
            $ids  = generateChildIDs($pdo, 'activity_tags', $activity_sys_id);
            $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
            $pdo->prepare("INSERT INTO activity_tags (uuid,sys_id,activity_sys_id,tag,status,meta_data)
                VALUES (?,?,?,?,'active',?)")
                ->execute([$ids['uuid'], $ids['sys_id'], $activity_sys_id, $tag, $meta]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success'  => true,
        'activity_sys_id' => $activity_sys_id,
        'tags'     => $tags,
        'count'    => count($tags),
        'message'  => count($tags) . ' tag(s) saved.',
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}