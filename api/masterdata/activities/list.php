<?php
/**
 * api/masterdata/activities/list.php (Gen-3)
 * GET ?search=&country_sys_id=&type=&tag=beach&status=active&page=1&limit=20
 * tag param: filter by activity_tags.tag  (comma-separated for multi: beach,family)
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$search  = trim($_GET['search']          ?? '');
$country = trim($_GET['country_sys_id']  ?? '');
$type    = trim($_GET['type']            ?? '');
$tagFlt  = trim($_GET['tag']             ?? '');   // comma-separated
$status  = trim($_GET['status']          ?? 'active');
$page    = max(1, (int)($_GET['page']    ?? 1));
$limit   = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset  = ($page - 1) * $limit;

try {
    $where  = [];
    $params = [];

    if ($status === 'trash')   $where[] = "a.status = 'deleted'";
    elseif ($status === 'all') $where[] = "a.status != 'deleted'";
    else                       $where[] = "a.status = 'active'";

    if ($country) { $where[] = "a.country_sys_id = :country"; $params[':country'] = $country; }
    if ($type)    { $where[] = "a.type = :type";              $params[':type']    = $type;    }
    if ($search)  { $where[] = "(a.name LIKE :s OR a.search_terms LIKE :s2 OR a.city_name LIKE :s3)";
                    $params[':s'] = "%{$search}%"; $params[':s2'] = "%{$search}%"; $params[':s3'] = "%{$search}%"; }

    // Tag filter — activity must have ALL specified tags
    $tagList = [];
    if ($tagFlt) {
        $tagList = array_filter(array_map('trim', explode(',', $tagFlt)));
    }

    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // If tag filter, use EXISTS subquery for each tag
    $tagSubqueries = '';
    foreach ($tagList as $i => $tag) {
        $key = ":tag{$i}";
        $tagSubqueries .= " AND EXISTS (
            SELECT 1 FROM activity_tags at{$i}
            WHERE at{$i}.activity_sys_id = a.sys_id
              AND at{$i}.tag = {$key}
              AND at{$i}.status = 'active'
        )";
        $params[$key] = $tag;
    }

    $fullWhere = $w . $tagSubqueries;

    // Count
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM activities a {$fullWhere}");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    // Fetch activities
    $stmt = $pdo->prepare("
        SELECT a.id, a.sys_id, a.uuid, a.country_sys_id, a.country_name,
               a.city_sys_id, a.city_name, a.name, a.type, a.category,
               a.location, a.short_description, a.duration_hours,
               a.popularity, a.usage_count, a.images, a.status
        FROM activities a
        {$fullWhere}
        ORDER BY a.usage_count DESC, a.popularity DESC, a.name ASC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Attach tags + thumb to each row
    if (!empty($rows)) {
        $sysIds      = array_column($rows, 'sys_id');
        $placeholders = implode(',', array_fill(0, count($sysIds), '?'));

        $tagStmt = $pdo->prepare("
            SELECT activity_sys_id, tag
            FROM activity_tags
            WHERE activity_sys_id IN ({$placeholders}) AND status = 'active'
            ORDER BY tag ASC
        ");
        $tagStmt->execute($sysIds);
        $tagMap = [];
        foreach ($tagStmt->fetchAll() as $t) {
            $tagMap[$t['activity_sys_id']][] = $t['tag'];
        }

        foreach ($rows as &$row) {
            $row['id']   = (int)$row['id'];
            $row['tags'] = $tagMap[$row['sys_id']] ?? [];
            // Extract thumb from images JSON
            $imgs = json_decode($row['images'] ?? '[]', true) ?: [];
            $row['thumb'] = $imgs[0]['url'] ?? null;
            unset($row['images']);
        }
        unset($row);
    }

    echo json_encode([
        'success'    => true,
        'data'       => $rows,
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