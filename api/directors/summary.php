<?php
// GET /api/directors/summary.php
require_once '../../server/db_connection.php';
require_once '../../server/director-calculation.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // $pdo variable-ti db_connection.php theke ashche
    $sql = "
        SELECT 
            COUNT(DISTINCT d.id) AS total_directors,
            COALESCE(
                SUM(CASE WHEN t.type='invest' THEN t.amount ELSE 0 END) - 
                SUM(CASE WHEN t.type='withdraw' THEN t.amount ELSE 0 END), 
            0) AS total_investment
        FROM directors d
        LEFT JOIN director_transactions t ON t.director_sys_id = d.sys_id COLLATE utf8mb4_unicode_ci
        WHERE d.status = 'active'
    ";

    // Prepare ebong Execute (Prepared statement style)
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(); // Default FETCH_ASSOC dewa ache connection-e

    if (!$row) {
        sendError('No data found', 404);
    }

    $totalInv   = (float)$row['total_investment'];
    $totalDirs  = (int)$row['total_directors'];
    
    // calcOwnership function-ti dhore nichchi upore define kora ache
    $totalOwn   = calcOwnership($totalInv);

    // Tomar sendSuccess helper bebohar kora holo
    sendSuccess([
        'total_directors'  => $totalDirs,
        'total_investment' => $totalInv,
        'total_ownership'  => $totalOwn,
        'base_unit'        => $cleanBasePrice,
        'base_ownership'   => 12.5
    ]);

} catch (Exception $e) {
    // Tomar sendError helper bebohar kora holo
    sendError($e->getMessage(), 500);
}
