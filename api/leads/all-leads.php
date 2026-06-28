<?php
// FILE PATH: /api/leads/all-leads.php
// GET ?trash=1  → deleted leads only
// GET           → active leads only

require '../../server/db_connection.php';
header('Content-Type: application/json');

$trash = isset($_GET['trash']) && $_GET['trash'] == '1';

try {
    $condition = $trash ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL';

    $sql = "
        SELECT *,
        STR_TO_DATE(
            JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.date')),
            '%d-%m-%Y %H:%i'
        ) AS extracted_date,
        JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.user')) AS created_by_name
        FROM leads
        WHERE {$condition}
        ORDER BY extracted_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count stats (always from active leads)
    $statsStmt = $pdo->prepare("
        SELECT lead_status, COUNT(*) AS cnt
        FROM leads
        WHERE deleted_at IS NULL
        GROUP BY lead_status
    ");
    $statsStmt->execute();
    $stats = ['all'=>0,'pending'=>0,'active'=>0,'converted'=>0,'hold'=>0,'closed'=>0];
    foreach ($statsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $stats[$row['lead_status']] = (int)$row['cnt'];
        $stats['all'] += (int)$row['cnt'];
    }

    echo json_encode(['leads' => $leads, 'stats' => $stats, 'success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}