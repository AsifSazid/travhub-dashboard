<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// include_once('../../authenticate.php');
require_once('../../server/db_connection.php');

$search           = trim($_GET['search'] ?? '');
$status_filter    = trim($_GET['status'] ?? 'all');
$completion_filter = trim($_GET['completion'] ?? '');
$page             = max(1, intval($_GET['page'] ?? 1));
$limit            = max(1, min(50, intval($_GET['limit'] ?? 12)));
$offset           = ($page - 1) * $limit;

try {
    // $pdo = getDBConnection();

    $where = [];
    $params = [];

    if ($status_filter === 'trash') {
        $where[] = "status = 'deleted'";
    } elseif ($status_filter === 'all') {
        $where[] = "status != 'deleted'";
    } else {
        $where[] = "status != 'deleted'";
        if ($status_filter === 'draft') {
            $where[] = "completion_status = 'draft'";
        } elseif ($status_filter === 'completed') {
            $where[] = "completion_status = 'completed'";
        } elseif ($status_filter === 'published') {
            $where[] = "completion_status = 'published'";
        }
    }

    if (!empty($search)) {
        $where[] = "(title LIKE :search OR sys_id LIKE :search2)";
        $params[':search']  = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM packages $whereSQL");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Fetch records
    $params[':limit']  = $limit;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare("
        SELECT uuid, sys_id, title, description, rating, image,
               countries, cities, duration, overall_price, currency_symbol,
               status, completion_status, progress_step, meta_data, created_at
        FROM packages
        $whereSQL
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        if ($key !== ':limit' && $key !== ':offset') {
            $stmt->bindValue($key, $val);
        }
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $packages = [];
    foreach ($rows as $row) {
        $meta = json_decode($row['meta_data'] ?? '{}', true);
        $packages[] = [
            'uuid'              => $row['uuid'],
            'sys_id'            => $row['sys_id'],
            'title'             => $row['title'],
            'description'       => $row['description'],
            'rating'            => $row['rating'],
            'image'             => $row['image'],
            'countries'         => json_decode($row['countries'] ?? '[]', true),
            'cities'            => json_decode($row['cities'] ?? '[]', true),
            'duration'          => $row['duration'],
            'overall_price'     => $row['overall_price'],
            'currency_symbol'   => $row['currency_symbol'],
            'status'            => $row['status'],
            'completion_status' => $row['completion_status'],
            'progress_step'     => $row['progress_step'],
            'created_by'        => $meta['created_by'] ?? 'N/A',
            'created_at'        => $row['created_at'],
        ];
    }

    echo json_encode([
        'success'     => true,
        'data'        => $packages,
        'pagination'  => [
            'total'        => intval($total),
            'page'         => $page,
            'limit'        => $limit,
            'total_pages'  => ceil($total / $limit),
        ],
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}