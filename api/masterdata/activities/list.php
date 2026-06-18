<?php
/**
 * api/masterdata/activities/list.php (Gen-3)
 * GET ?search=&country_sys_id=&type=&status=active&page=1&limit=20
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$search  = trim($_GET['search']         ?? '');
$country = trim($_GET['country_sys_id'] ?? '');
$type    = trim($_GET['type']            ?? '');
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

    $fullWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM activities a {$fullWhere}");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    // Fetch activities
    $stmt = $pdo->prepare("
        SELECT a.id, a.sys_id, a.uuid, a.country_sys_id, a.country_name,
               a.city_sys_id, a.city_name, a.name, a.type, a.category,
               a.short_description, a.duration_hours,
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

    // Format rows and extract thumbnails
    if (!empty($rows)) {
        foreach ($rows as &$row) {
            $row['id']   = (int)$row['id'];
            
            // If you still need a 'tags' key in the JSON output to prevent frontend breaking,
            // we can leave it as an empty array, or you can delete this line if it's no longer needed.
            $row['tags'] = []; 
            
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