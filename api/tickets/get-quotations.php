<?php

require '../../server/db_connection.php';

header('Content-Type: application/json');

// 1. Capture and Sanitize Inputs
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page   = isset($_GET['page'])   ? (int)$_GET['page']  : 1;
$limit  = isset($_GET['limit'])  ? (int)$_GET['limit'] : 12;

// Calculate offset for SQL
$offset = ($page - 1) * $limit;

// 2. Validate Sort (Prevent SQL Injection)
$allowedSortColumns = ['created_at', 'title', 'sys_id'];
$sortColumn = 'created_at'; 
$sortOrder  = 'DESC';

if (isset($_GET['sort'])) {
    $parts = explode(' ', $_GET['sort']);
    if (in_array($parts[0], $allowedSortColumns)) {
        $sortColumn = $parts[0];
    }
    if (isset($parts[1]) && strtoupper($parts[1]) === 'ASC') {
        $sortOrder = 'ASC';
    }
}

try {
    // 3. Get Total Count (For pagination metadata)
    $countSql = "SELECT COUNT(*) FROM air_ticket_quatations 
                 WHERE title LIKE :search 
                 OR client_sys_id LIKE :search 
                 OR sys_id LIKE :search";
    
    $countStmt = $pdo->prepare($countSql);
    $searchTerm = "%$search%";
    $countStmt->execute(['search' => $searchTerm]);
    $totalRows = (int)$countStmt->fetchColumn();

    // 4. Fetch Paginated Data
    $dataSql = "SELECT 
                    sys_id, 
                    title, 
                    client_sys_id, 
                    JSON_LENGTH(COALESCE(informations, '[]')) AS quotation_count, 
                    created_at 
                FROM air_ticket_quatations
                WHERE title LIKE :search 
                   OR client_sys_id LIKE :search 
                   OR sys_id LIKE :search
                ORDER BY $sortColumn $sortOrder
                LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($dataSql);
    $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':limit',  $limit,      PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Success Response
    echo json_encode([
        'success' => true,
        'total'   => $totalRows,
        'page'    => $page,
        'limit'   => $limit,
        'data'    => $results
    ]);

} catch (Exception $e) {
    // 6. Error Response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}