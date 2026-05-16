<?php
session_start();

require '../../server/db_connection.php';

header('Content-Type: application/json');

// ── Params ────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page']  ?? 1));
$limit  = max(1, min(48, (int)($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;

$allowedSorts = [
    'created_at DESC',
    'created_at ASC',
    'title ASC',
    'title DESC',
];
$sort = in_array($_GET['sort'] ?? '', $allowedSorts)
    ? $_GET['sort']
    : 'created_at DESC';

// ── WHERE clause ──────────────────────────────────────────────
$where  = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= " AND (
        aq.title            LIKE :search
        OR aq.sys_id        LIKE :search
        OR aq.client_sys_id LIKE :search
        OR cl.name          LIKE :search
    )";
    $params[':search'] = '%' . $search . '%';
}

// ── Total count ───────────────────────────────────────────────
$countSql = "
    SELECT COUNT(*)
    FROM air_ticket_quotations aq
    LEFT JOIN clients cl ON cl.sys_id = aq.client_sys_id
    $where
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// ── Data query ────────────────────────────────────────────────
$dataSql = "
    SELECT
        aq.sys_id,
        aq.client_sys_id,
        aq.title,
        cl.name                      AS client_name,
        JSON_LENGTH(aq.informations) AS quotation_count,
        aq.created_at
    FROM air_ticket_quotations aq
    LEFT JOIN clients cl ON cl.sys_id = aq.client_sys_id
    $where
    ORDER BY aq.$sort
    LIMIT :limit OFFSET :offset
";

$dataStmt = $pdo->prepare($dataSql);

foreach ($params as $key => $val) {
    $dataStmt->bindValue($key, $val);
}

$dataStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();

$rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Format ────────────────────────────────────────────────────
foreach ($rows as &$row) {
    $row['quotation_count'] = (int) ($row['quotation_count'] ?? 0);
    $row['client_name']     = $row['client_name'] ?? $row['client_sys_id'] ?? '—';
}
unset($row);

// ── Response ──────────────────────────────────────────────────
echo json_encode([
    'success' => true,
    'total'   => $total,
    'page'    => $page,
    'limit'   => $limit,
    'data'    => $rows,
], JSON_UNESCAPED_UNICODE);